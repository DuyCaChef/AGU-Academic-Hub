<?php

namespace App\Http\Requests\Documents;

use App\Models\TaiLieu;
use App\Services\Documents\Validation\FileSecurityValidator;
use Illuminate\Foundation\Http\FormRequest;

class CreateDocumentVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }

        // Chặn tài khoản bị khóa
        if ($user->trang_thai === 'TAM_KHOA') {
            return false;
        }

        $documentId = $this->route('id');
        $document = TaiLieu::query()->find($documentId);

        // Nếu tài liệu không tồn tại, trả về 404
        if (!$document) {
            abort(404, 'Tài liệu không tồn tại trong hệ thống.');
        }

        // Xác định tài liệu gốc nếu ID truyền vào là một phiên bản con
        $rootDoc = $document->ma_tai_lieu_goc ? TaiLieu::query()->find($document->ma_tai_lieu_goc) : $document;
        if (!$rootDoc) {
            $rootDoc = $document;
        }

        // Chỉ chủ sở hữu tài liệu gốc và Quản trị viên mới được phép tạo version mới
        $isOwner = ((int) $rootDoc->ma_nguoi_dang === (int) $user->ma_nguoi_dung);
        $isAdmin = (method_exists($user, 'laQuanTriVien') && $user->laQuanTriVien());

        return $isOwner || $isAdmin;
    }

    public function rules(): array
    {
        return [
            'tieu_de'   => ['nullable', 'string', 'max:255'],
            'mo_ta'     => ['nullable', 'string', 'max:5000'],
            'phien_ban' => ['required', 'string', 'max:20'],
            'file'      => [
                'required',
                'file',
                'max:51200',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if ($value instanceof \Illuminate\Http\UploadedFile) {
                        $error = FileSecurityValidator::validate($value);
                        if ($error !== null) {
                            $fail($error);
                        }
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'phien_ban.required' => 'Vui lòng nhập định danh phiên bản (ví dụ: 1.1, 2.0).',
            'file.required'      => 'Vui lòng chọn tệp tin phiên bản mới.',
            'file.file'          => 'Tệp tải lên không hợp lệ.',
            'file.mimes'         => 'Chỉ chấp nhận các tệp định dạng: PDF, DOCX, PPTX, XLSX, TXT.',
            'file.max'           => 'Dung lượng tệp tin không được vượt quá 50MB.',
        ];
    }
}
