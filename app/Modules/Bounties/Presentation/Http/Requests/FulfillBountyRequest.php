<?php

namespace App\Modules\Bounties\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// Form Request xác thực dữ liệu khi gửi tài liệu phản hồi yêu cầu
class FulfillBountyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        if ($this->hasFile('file')) {
            return [
                'file'             => ['required', 'file', 'mimes:pdf,docx,pptx,zip', 'max:51200'],
                'tieu_de'          => ['required', 'string', 'max:255'],
                'phan_loai'        => ['nullable', 'string', 'in:DE_THI,BAI_GIANG,DO_AN,BAI_TAP,KHAC'],
                'ghi_chu_dong_gop' => ['nullable', 'string', 'max:500'],
            ];
        }

        return [
            'ma_tai_lieu'      => ['required', 'integer', 'exists:tai_lieu,ma_tai_lieu'],
            'ghi_chu_dong_gop' => ['nullable', 'string', 'max:500'],
        ];
    }
}

