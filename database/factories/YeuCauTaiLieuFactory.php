<?php

namespace Database\Factories;

use App\Enums\TrangThaiYeuCauTaiLieu;
use App\Models\MonHoc;
use App\Models\NguoiDung;
use App\Models\YeuCauTaiLieu;
use Illuminate\Database\Eloquent\Factories\Factory;

class YeuCauTaiLieuFactory extends Factory
{
    protected $model = YeuCauTaiLieu::class;

    public function definition(): array
    {
        return [
            'ma_mon_hoc' => MonHoc::factory(),
            'ma_nguoi_tao' => NguoiDung::factory(),
            'tieu_de' => 'Cần tìm ' . fake('vi_VN')->sentence(4),
            'mo_ta' => fake('vi_VN')->paragraph(2),
            'ten_giang_vien' => 'ThS. ' . fake('vi_VN')->name(),
            'phan_loai' => fake()->randomElement(['DE_THI', 'BAI_GIANG', 'DO_AN', 'BAI_TAP']),
            'diem_thuong' => fake()->randomElement([20, 50, 100, 200]),
            'trang_thai' => TrangThaiYeuCauTaiLieu::MO,
            'ma_tai_lieu_dap_ung' => null,
            'nguoi_xac_nhan' => null,
            'thoi_gian_xac_nhan' => null,
        ];
    }
}
