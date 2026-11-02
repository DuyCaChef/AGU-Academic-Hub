<?php

namespace App\Modules\Bounties\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// Form Request xác thực dữ liệu khi xác nhận giải ngân tài liệu đáp ứng
class ConfirmBountyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'ma_dap_ung' => ['required', 'integer', 'exists:dap_ung_yeu_cau_tai_lieu,ma_dap_ung'],
        ];
    }
}

