<?php

namespace Database\Seeders;

use App\Enums\TrangThaiNguoiDung;
use App\Enums\VaiTroNguoiDung;
use App\Models\DanhGiaMonHoc;
use App\Models\Khoa;
use App\Models\MonHoc;
use App\Models\NguoiDung;
use App\Models\YeuCauTaiLieu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CommunitySeeder extends Seeder
{
    public function run(): void
    {
        // 1. Tạo Khoa CNTT mặc định nếu chưa có
        $khoa = Khoa::firstOrCreate(
            ['ky_hieu_khoa' => 'CNTT'],
            [
                'ten_khoa' => 'Khoa Công nghệ Thông tin',
            ]
        );

        // 2. Tạo môn học mẫu nếu chưa có
        $monHoc = MonHoc::firstOrCreate(
            ['ma_hoc_phan' => 'COS101'],
            [
                'ma_khoa' => $khoa->ma_khoa,
                'ten_mon_hoc' => 'Lập trình căn bản',
                'so_tin_chi' => 3,
                'mo_ta' => 'Nhập môn lập trình cấu trúc và giải thuật căn bản.',
            ]
        );

        // 3. Tạo tài khoản Admin mặc định
        $admin = NguoiDung::firstOrCreate(
            ['email' => 'admin@agu.edu.vn'],
            [
                'ma_khoa' => $khoa->ma_khoa,
                'ho_ten' => 'Quản trị viên Hệ thống',
                'mat_khau' => Hash::make('Admin@123456'),
                'vai_tro' => VaiTroNguoiDung::QUAN_TRI_VIEN,
                'trang_thai' => TrangThaiNguoiDung::DANG_HOAT_DONG,
                'xac_thuc_email_luc' => now(),
            ]
        );

        // 4. Tạo tài khoản Moderator mặc định
        $mod = NguoiDung::firstOrCreate(
            ['email' => 'mod@agu.edu.vn'],
            [
                'ma_khoa' => $khoa->ma_khoa,
                'ho_ten' => 'Ban Học Tập Moderation',
                'mat_khau' => Hash::make('Mod@123456'),
                'vai_tro' => VaiTroNguoiDung::KIEM_DUYET_VIEN,
                'trang_thai' => TrangThaiNguoiDung::DANG_HOAT_DONG,
                'xac_thuc_email_luc' => now(),
            ]
        );

        // 5. Tạo tài khoản Sinh viên kiểm thử
        $student = NguoiDung::firstOrCreate(
            ['email' => 'duy_tester@student.agu.edu.vn'],
            [
                'ma_khoa' => $khoa->ma_khoa,
                'ho_ten' => 'Khánh Duy Tester',
                'mssv' => 'DTH215001',
                'mat_khau' => Hash::make('Duy@123456'),
                'vai_tro' => VaiTroNguoiDung::SINH_VIEN,
                'trang_thai' => TrangThaiNguoiDung::DANG_HOAT_DONG,
                'xac_thuc_email_luc' => now(),
            ]
        );

        // 6. Tạo 10 Sinh viên ngẫu nhiên bằng Factory
        NguoiDung::factory()->count(10)->create();

        // 7. Tạo dữ liệu Review và Wishlist mẫu cho các môn học có sẵn
        $monHocs = MonHoc::take(5)->get();
        if ($monHocs->isNotEmpty()) {
            foreach ($monHocs as $mon) {
                // Tạo review mẫu (mỗi user 1 review / học kỳ để thỏa mãn unique key)
                DanhGiaMonHoc::firstOrCreate(
                    [
                        'ma_mon_hoc' => $mon->ma_mon_hoc,
                        'ma_nguoi_dung' => $student->ma_nguoi_dung,
                        'hoc_ky' => '2025-2',
                    ],
                    [
                        'diem_do_kho' => 4,
                        'diem_thuc_tien' => 5,
                        'diem_khoi_luong' => 3,
                        'noi_dung' => 'Môn học rất thực tế, giảng viên nhiệt tình hỗ trợ thực hành.',
                        'an_danh' => true,
                    ]
                );

                // Tạo Wishlist / Yêu cầu tài liệu mẫu
                YeuCauTaiLieu::create([
                    'ma_mon_hoc' => $mon->ma_mon_hoc,
                    'ma_nguoi_tao' => $student->ma_nguoi_dung,
                    'tieu_de' => "Xin đề thi kết thúc học phần môn {$mon->ten_mon_hoc}",
                    'mo_ta' => 'Cần xin file PDF lời giải đề thi cuối kỳ đợt 1 năm học vừa rồi.',
                    'ten_giang_vien' => 'Ban Giảng viên Bộ môn',
                    'phan_loai' => 'DE_THI',
                    'diem_thuong' => 50,
                ]);
            }
        }
    }
}