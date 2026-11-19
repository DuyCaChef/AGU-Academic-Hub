<?php

namespace App\Http\Requests\Community;

use Illuminate\Foundation\Http\FormRequest;

class RejectBountyPayoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ly_do' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'ly_do.required' => 'Vui lòng nhập lý do từ chối giải ngân.',
            'ly_do.min' => 'Lý do từ chối phải có ít nhất 10 ký tự.',
        ];
    }
}
