<?php

namespace App\Modules\Bounties\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectBountyFulfillmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'ma_dap_ung' => ['required', 'integer', 'exists:dap_ung_yeu_cau_tai_lieu,ma_dap_ung'],
            'ly_do'      => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'ma_dap_ung.required' => 'Mã bài đóng góp là bắt buộc.',
            'ma_dap_ung.exists'   => 'Bài đóng góp không tồn tại trong hệ thống.',
            'ly_do.required'      => 'Vui lòng cung cấp lý do từ chối để người đóng góp được biết.',
            'ly_do.min'           => 'Lý do từ chối phải có ít nhất 5 ký tự.',
            'ly_do.max'           => 'Lý do từ chối không được vượt quá 1000 ký tự.',
        ];
    }
}

