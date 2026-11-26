<?php

namespace App\Http\Requests\Moderation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RemovalDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['APPROVE', 'REJECT'])],
            'decision_reason' => ['nullable', 'required_if:action,REJECT', 'string', 'max:2000'],
        ];
    }
}
