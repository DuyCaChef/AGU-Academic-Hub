<?php

namespace App\Http\Requests\Moderation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportResolutionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['HIDE', 'DELETE', 'DISMISS'])],
            'resolution_reason' => ['required', 'string', 'max:2000'],
        ];
    }
}
