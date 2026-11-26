<?php

namespace Database\Factories;

use App\Enums\TrangThaiDanhGiaMonHoc;
use App\Models\DanhGiaMonHoc;
use App\Models\MonHoc;
use App\Models\NguoiDung;
use Illuminate\Database\Eloquent\Factories\Factory;

class DanhGiaMonHocFactory extends Factory
{
    protected $model = DanhGiaMonHoc::class;

    public function definition(): array
    {
        return [
            'ma_mon_hoc' => MonHoc::factory(),
            'ma_nguoi_dung' => NguoiDung::factory(),
            'hoc_ky' => fake()->randomElement(['2025-1', '2025-2', '2026-1']),
            'diem_do_kho' => fake()->numberBetween(1, 5),
            'diem_thuc_tien' => fake()->numberBetween(1, 5),
            'diem_khoi_luong' => fake()->numberBetween(1, 5),
            'noi_dung' => fake('vi_VN')->paragraph(3),
            'an_danh' => fake()->boolean(70),
            'trang_thai' => TrangThaiDanhGiaMonHoc::DA_DUYET,
        ];
    }
}
