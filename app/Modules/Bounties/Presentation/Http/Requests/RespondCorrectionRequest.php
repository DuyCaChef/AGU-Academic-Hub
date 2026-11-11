<?php

namespace App\Modules\Bounties\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RespondCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'hanh_dong'     => ['required', 'string', 'in:CHAP_NHAN,TU_CHOI'],
            'ly_do_tu_choi' => ['nullable', 'required_if:hanh_dong,TU_CHOI', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'hanh_dong.required'              => 'Vui lòng chọn phản hồi (Chấp nhận hoặc Từ chối).',
            'hanh_dong.in'                    => 'Hành động phản hồi không hợp lệ.',
            'ly_do_tu_choi.required_if'       => 'Vui lòng cung cấp lý do khi từ chối yêu cầu chỉnh sửa.',
        ];
    }
}

