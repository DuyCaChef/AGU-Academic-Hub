<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RejectKycRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ly_do_tu_choi' => ['required', 'string', 'min:6', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'ly_do_tu_choi.required' => 'Bắt buộc phải nhập lý do từ chối hồ sơ.',
            'ly_do_tu_choi.min' => 'Lý do từ chối tối thiểu 6 ký tự.',
        ];
    }
}
