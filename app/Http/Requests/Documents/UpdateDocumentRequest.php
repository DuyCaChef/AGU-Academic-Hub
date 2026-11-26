<?php

namespace App\Http\Requests\Documents;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tieu_de' => ['required', 'string', 'max:255'],
            'mo_ta' => ['nullable', 'string', 'max:5000'],
            'phan_loai' => ['nullable', 'string', 'max:50'],
        ];
    }
}
