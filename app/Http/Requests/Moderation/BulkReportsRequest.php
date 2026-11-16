<?php

namespace App\Http\Requests\Moderation;

use Illuminate\Foundation\Http\FormRequest;

class BulkReportsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reports' => ['required', 'array', 'min:1'],
            'reports.*.id' => ['required', 'integer'],
            'reports.*.type' => ['required', 'string', 'in:MON_HOC,GIANG_VIEN,TAI_LIEU'],
            'resolution_reason' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reports.required' => 'Vui lòng chọn ít nhất một báo cáo để thao tác.',
            'reports.min' => 'Vui lòng chọn ít nhất một báo cáo để thao tác.',
        ];
    }
}
