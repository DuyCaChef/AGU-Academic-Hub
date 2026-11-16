<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserPermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vai_tro' => ['required', 'in:SINH_VIEN,CUU_SINH_VIEN,KIEM_DUYET_VIEN,QUAN_TRI_VIEN'],
            'ma_khoa' => ['nullable', 'integer', 'exists:khoa,ma_khoa'],
            'cap_quan_tri' => ['nullable', 'in:THUONG,TOI_CAO'],
            'ly_do' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
