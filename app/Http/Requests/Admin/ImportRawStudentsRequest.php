<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ImportRawStudentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'records' => ['required', 'array', 'min:1'],
            'records.*.mssv' => ['required', 'string', 'max:50'],
            'records.*.ho_ten' => ['required', 'string', 'max:255'],
            'records.*.email_sv' => ['nullable', 'email', 'max:191'],
            'records.*.ma_khoa' => ['nullable', 'string', 'max:20'],
        ];
    }
}
