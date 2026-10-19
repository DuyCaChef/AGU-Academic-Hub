<?php

namespace Database\Factories;

use App\Enums\TrangThaiNguoiDung;
use App\Enums\VaiTroNguoiDung;
use App\Models\Khoa;
use App\Models\NguoiDung;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class NguoiDungFactory extends Factory
{
    protected $model = NguoiDung::class;

    protected static ?string $password;

    public function definition(): array
    {
        $majorPrefixes = [
            'DTH', // Đại học Công nghệ thông tin 
            'DPM', // Đại học Kỹ thuật phần mềm
            'DMK', // Đại học Marketing
            'DQT', // Đại học Quản trị kinh doanh
            'DTC', // Đại học Tài chính - Ngân hàng
            'DKT', // Đại học Kế toán
            'DTA', // Đại học Ngôn ngữ Anh / Sư phạm Tiếng Anh
            'DLU', // Đại học Luật
            'DTP', // Đại học Công nghệ thực phẩm
            'DNN', // Đại học Nông nghiệp
            'DBT', // Đại học Bảo vệ thực vật
            'DTS', // Đại học Thủy sản
            'DMN', // Đại học Giáo dục Mầm non
            'DGD', // Đại học Giáo dục Tiểu học
        ];

        $prefix = fake()->randomElement($majorPrefixes);
        $cohortYear = fake()->numberBetween(20, 25);
        $studentIndex = fake()->unique()->numberBetween(1000, 9999);
        $studentCode = "{$prefix}{$cohortYear}{$studentIndex}";
        $email = strtolower($studentCode) . '@student.agu.edu.vn';

        return [
            'ma_khoa' => Khoa::inRandomOrder()->value('ma_khoa') ?? null,
            'ho_ten' => fake('vi_VN')->name(),
            'email' => $email,
            'mssv' => $studentCode,
            'mat_khau' => static::$password ??= Hash::make('password123'),
            'vai_tro' => VaiTroNguoiDung::SINH_VIEN,
            'trang_thai' => TrangThaiNguoiDung::DANG_HOAT_DONG,
            'diem_uy_tin' => 100,
            'anh_dai_dien' => null,
            'xac_thuc_email_luc' => now(),
            'nam_tot_nghiep' => null,
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn(array $attributes) => [
            'vai_tro' => VaiTroNguoiDung::QUAN_TRI_VIEN,
            'email' => 'admin@agu.edu.vn',
            'mssv' => null,
            'ma_khoa' => null,
        ]);
    }

    public function moderator(): static
    {
        return $this->state(fn(array $attributes) => [
            'vai_tro' => VaiTroNguoiDung::KIEM_DUYET_VIEN,
            'email' => 'moderator@agu.edu.vn',
            'mssv' => null,
        ]);
    }

    public function alumni(): static
    {
        return $this->state(fn(array $attributes) => [
            'vai_tro' => VaiTroNguoiDung::CUU_SINH_VIEN,
            'nam_tot_nghiep' => fake()->numberBetween(2020, 2024),
        ]);
    }
}
