<?php

namespace App\Http\Requests\Moderation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DocumentReviewDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['KEEP', 'RESTORE', 'HIDE', 'DELETE'])],
            'resolution_reason' => ['required', 'string', 'max:2000'],
        ];
    }
}
