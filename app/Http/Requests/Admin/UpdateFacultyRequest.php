<?php

namespace App\Http\Requests\Admin;

use App\Models\Khoa;
use Illuminate\Validation\Rule;

class UpdateFacultyRequest extends AcademicCatalogRequest
{
    public function rules(): array
    {
        $faculty = Khoa::withTrashed()->findOrFail($this->route('maKhoa'));

        return [
            'ky_hieu_khoa' => [
                'required',
                'string',
                'regex:/^[A-Z0-9-]{2,20}$/',
                Rule::unique('khoa', 'ky_hieu_khoa')->ignore($faculty->ma_khoa, 'ma_khoa'),
            ],
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
