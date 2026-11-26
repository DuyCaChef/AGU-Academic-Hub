<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class StoreCourseRequest extends AcademicCatalogRequest
{
    public function rules(): array
    {
        return $this->courseRules();
    }

    private function courseRules(): array
    {
        return [
            'ma_khoa' => ['required', 'integer', Rule::exists('khoa', 'ma_khoa')->whereNull('ngay_xoa_tam')],
            // Mã học phần gồm đúng 3 chữ cái và 3 chữ số, ví dụ PKD101.
            'ma_hoc_phan' => ['required', 'string', 'regex:/^[A-Z]{3}[0-9]{3}$/', 'unique:mon_hoc,ma_hoc_phan'],
            'ten_mon_hoc' => ['required', 'string', 'max:191'],
            'so_tin_chi' => ['required', 'integer', 'min:1', 'max:8'],
            'mo_ta' => ['nullable', 'string'],
            'ma_chuyen_nganh' => ['nullable', 'array', 'distinct'],
            'ma_chuyen_nganh.*' => [
                'integer',
                Rule::exists('chuyen_nganh', 'ma_chuyen_nganh')
                    ->where('ma_khoa', $this->input('ma_khoa'))
                    ->whereNull('ngay_xoa_tam'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'ma_khoa.exists' => 'Khoa không tồn tại hoặc đang bị vô hiệu hóa.',
            'ma_hoc_phan.regex' => 'Mã học phần phải gồm đúng 3 chữ cái và 3 chữ số, ví dụ PKD101.',
            'ma_hoc_phan.unique' => 'Mã học phần đã tồn tại.',
            'ten_mon_hoc.required' => 'Vui lòng nhập tên môn học.',
            'so_tin_chi.min' => 'Số tín chỉ phải từ 1 đến 8.',
            'so_tin_chi.max' => 'Số tín chỉ phải từ 1 đến 8.',
            'ma_chuyen_nganh.array' => 'Danh sách chuyên ngành không hợp lệ.',
            'ma_chuyen_nganh.distinct' => 'Danh sách chuyên ngành không được có mã trùng nhau.',
            'ma_chuyen_nganh.*.exists' => 'Một hoặc nhiều chuyên ngành không tồn tại, đã bị vô hiệu hóa hoặc không thuộc Khoa đã chọn.',
        ];
    }
}
