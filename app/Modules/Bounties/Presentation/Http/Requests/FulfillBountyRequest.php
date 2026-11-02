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
        return [
            'ma_tai_lieu'      => ['required', 'integer', 'exists:tai_lieu,ma_tai_lieu'],
            'ghi_chu_dong_gop' => ['nullable', 'string', 'max:500'],
        ];
    }
}

