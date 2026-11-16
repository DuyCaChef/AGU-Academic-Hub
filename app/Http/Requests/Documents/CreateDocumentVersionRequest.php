<?php

namespace App\Http\Requests\Documents;

use Illuminate\Foundation\Http\FormRequest;

class CreateDocumentVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tieu_de' => ['nullable', 'string', 'max:255'],
            'mo_ta' => ['nullable', 'string', 'max:5000'],
            'phien_ban' => ['required', 'string', 'max:20'],
            'file' => ['required', 'file', 'max:51200'],
        ];
    }
}
