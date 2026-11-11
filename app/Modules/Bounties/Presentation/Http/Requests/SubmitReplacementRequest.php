<?php

namespace App\Modules\Bounties\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitReplacementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'file'        => ['nullable', 'file', 'mimes:pdf,docx,pptx,zip', 'max:51200'],
            'ma_tai_lieu' => ['nullable', 'integer', 'exists:tai_lieu,ma_tai_lieu'],
            'tieu_de'     => ['nullable', 'string', 'max:255'],
            'ghi_chu'     => ['nullable', 'string', 'max:1000'],
            'phan_loai'   => ['nullable', 'string', 'in:DE_THI,BAI_GIANG,DO_AN,BAI_TAP,GIAO_TRINH,KHAC'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.mimes'      => 'Định dạng tệp không được hỗ trợ. Chỉ chấp nhận PDF, DOCX, PPTX hoặc ZIP.',
            'file.max'        => 'Dung lượng tệp tải lên không được vượt quá 50MB.',
            'ma_tai_lieu.exists' => 'Tài liệu đã chọn không tồn tại trong hệ thống.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (!$this->hasFile('file') && empty($this->input('ma_tai_lieu'))) {
                $validator->errors()->add('file', 'Vui lòng tải lên tệp thay thế mới hoặc chọn tài liệu sẵn có từ kho cá nhân.');
            }
        });
    }
}

