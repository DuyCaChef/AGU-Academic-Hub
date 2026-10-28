<?php

namespace App\Http\Requests\Admin;

use App\Models\ChuyenNganh;
use Illuminate\Validation\Rule;

class UpdateMajorRequest extends AcademicCatalogRequest
{
    public function rules(): array
    {
        $major = ChuyenNganh::withTrashed()->findOrFail($this->route('maChuyenNganh'));

        return [
            'ma_khoa' => ['required', 'integer', Rule::exists('khoa', 'ma_khoa')->whereNull('ngay_xoa_tam')],
            'ky_hieu_nganh' => [
                'required',
                'string',
                // Mã ngành theo quy ước: chữ D + đúng 2 chữ cái, ví dụ DMK, DTH.
                'regex:/^D[A-Z]{2}$/',
                Rule::unique('chuyen_nganh', 'ky_hieu_nganh')->ignore($major->ma_chuyen_nganh, 'ma_chuyen_nganh'),
            ],
            'ten_chuyen_nganh' => ['required', 'string', 'max:191'],
            'mo_ta' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'ma_khoa.exists' => 'Khoa không tồn tại hoặc đang bị vô hiệu hóa.',
            'ky_hieu_nganh.regex' => 'Mã chuyên ngành phải có dạng DXX, gồm chữ D và 2 chữ cái, ví dụ DMK.',
            'ky_hieu_nganh.unique' => 'Mã chuyên ngành đã tồn tại.',
            'ten_chuyen_nganh.required' => 'Vui lòng nhập tên chuyên ngành.',
        ];
    }
}
