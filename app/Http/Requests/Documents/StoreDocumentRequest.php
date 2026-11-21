<?php

namespace App\Http\Requests\Documents;

use App\Services\Documents\Validation\FileSecurityValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }

        // Chặn tài khoản bị tạm khóa
        if ($user->trang_thai === 'TAM_KHOA') {
            return false;
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'ma_mon_hoc' => ['required', 'integer', 'exists:mon_hoc,ma_mon_hoc'],
            'tieu_de'    => ['required', 'string', 'max:255'],
            'mo_ta'      => ['nullable', 'string', 'max:5000'],
            'phan_loai'  => [
                'nullable',
                'string',
                Rule::in(['DE_THI', 'BAI_GIANG', 'DO_AN', 'BAI_TAP', 'KHAC']),
            ],
            'file'       => [
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
            'ma_mon_hoc.required' => 'Vui lòng chọn môn học.',
            'ma_mon_hoc.exists'   => 'Môn học đã chọn không tồn tại trong hệ thống.',
            'tieu_de.required'    => 'Vui lòng nhập tiêu đề tài liệu.',
            'tieu_de.max'         => 'Tiêu đề không được vượt quá 255 ký tự.',
            'phan_loai.in'        => 'Phân loại tài liệu không hợp lệ. Chỉ chấp nhận: Đề thi, Bài giảng, Đồ án, Bài tập hoặc Khác.',
            'file.required'       => 'Vui lòng chọn tệp tài liệu cần tải lên.',
            'file.file'           => 'Tệp tải lên không hợp lệ.',
            'file.mimes'          => 'Chỉ chấp nhận các tệp định dạng: PDF, DOCX, PPTX, XLSX, TXT.',
            'file.max'            => 'Dung lượng tệp tin không được vượt quá 50MB.',
        ];
    }
}
