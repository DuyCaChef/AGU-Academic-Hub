<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

abstract class AcademicCatalogRequest extends FormRequest
{
    /**
     * Chỉ Quản trị viên được phép thêm/sửa danh mục học thuật.
     */
    public function authorize(): bool
    {
        return $this->user()?->laQuanTriVien() ?? false;
    }

    /**
     * Chuẩn hóa mã trước khi chạy validation để dữ liệu được lưu nhất quán.
     */
    protected function prepareForValidation(): void
    {
        $data = [];

        foreach (['ky_hieu_khoa', 'ky_hieu_nganh', 'ma_hoc_phan'] as $field) {
            if ($this->has($field)) {
                $data[$field] = mb_strtoupper(trim((string) $this->input($field)));
            }
        }

        foreach (['ten_khoa', 'ten_chuyen_nganh', 'ten_mon_hoc', 'mo_ta'] as $field) {
            if ($this->has($field) && is_string($this->input($field))) {
                $data[$field] = trim($this->input($field));
            }
        }

        if ($data !== []) {
            $this->merge($data);
        }
    }
}
