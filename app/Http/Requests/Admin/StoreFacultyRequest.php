<?php

namespace App\Http\Requests\Admin;

class StoreFacultyRequest extends AcademicCatalogRequest
{
    public function rules(): array
    {
        return [
            'ky_hieu_khoa' => ['required', 'string', 'regex:/^[A-Z0-9-]{2,20}$/', 'unique:khoa,ky_hieu_khoa'],
            'ten_khoa' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'ky_hieu_khoa.regex' => 'Mã Khoa chỉ gồm chữ cái, chữ số, dấu gạch ngang và dài từ 2 đến 20 ký tự.',
            'ky_hieu_khoa.unique' => 'Mã Khoa đã tồn tại.',
            'ten_khoa.required' => 'Vui lòng nhập tên Khoa.',
        ];
    }
}
