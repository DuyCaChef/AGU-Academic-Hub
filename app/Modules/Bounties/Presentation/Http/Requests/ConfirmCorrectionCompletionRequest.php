<?php

namespace App\Modules\Bounties\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmCorrectionCompletionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'ma_lan_nop_thay_the' => ['required', 'integer', 'exists:lan_nop_tai_lieu_thay_the,ma_lan_nop_thay_the'],
        ];
    }

    public function messages(): array
    {
        return [
            'ma_lan_nop_thay_the.required' => 'Vui lòng chọn phiên bản tài liệu nghiệm thu.',
            'ma_lan_nop_thay_the.exists'   => 'Phiên bản tài liệu không tồn tại.',
        ];
    }
}

