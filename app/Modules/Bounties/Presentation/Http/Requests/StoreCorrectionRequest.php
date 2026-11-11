<?php

namespace App\Modules\Bounties\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'ly_do_chinh_sua' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'ly_do_chinh_sua.required' => 'Vui lòng nhập lý do hoặc nội dung cần chỉnh sửa, bổ sung.',
            'ly_do_chinh_sua.min'      => 'Nội dung yêu cầu chỉnh sửa phải có ít nhất 10 ký tự.',
            'ly_do_chinh_sua.max'      => 'Nội dung yêu cầu chỉnh sửa không được vượt quá 2000 ký tự.',
        ];
    }
}

