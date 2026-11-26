<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRawStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ho_ten' => ['required', 'string', 'max:255'],
            'email_sv' => ['nullable', 'email', 'max:191'],
            'ma_khoa' => ['nullable', 'string', 'max:20'],
            'mat_khau_mac_dinh' => ['nullable', 'string', 'min:6'],
        ];
    }
}
