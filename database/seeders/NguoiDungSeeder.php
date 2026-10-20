<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class NguoiDungSeeder extends Seeder
{
    public function run(): void
    {
        $maKhoa = DB::table('khoa')
            ->where('ky_hieu_khoa', 'CNTT')
            ->value('ma_khoa');

        $users = [
            [
                'email' => 'sinhvien@agu.edu.vn',
                'ho_ten' => 'Nguyễn Văn An',
                'mssv' => 'DTH225001',
                'vai_tro' => 'SINH_VIEN',
                'diem_uy_tin' => 100,
            ],
            [
                'email' => 'kiemduyet@agu.edu.vn',
                'ho_ten' => 'Trần Thị Bình',
                'mssv' => null,
                'vai_tro' => 'KIEM_DUYET_VIEN',
                'diem_uy_tin' => 100,
            ],
        ];

        foreach ($users as $user) {
            DB::table('nguoi_dung')->updateOrInsert(
                ['email' => $user['email']],
                $user + [
                    'ma_khoa' => $maKhoa,
                    'mat_khau' => Hash::make('Demo@123456'),
                    'trang_thai' => 'DANG_HOAT_DONG',
                    'xac_thuc_email_luc' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}