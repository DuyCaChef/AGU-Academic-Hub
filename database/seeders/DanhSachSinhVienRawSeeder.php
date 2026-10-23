<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DanhSachSinhVienRawSeeder extends Seeder
{
    /**
     * Nạp dữ liệu thô mẫu sinh viên do nhà trường cấp.
     * Mật khẩu mặc định: Agu@123456
     */
    public function run(): void
    {
        $defaultPassword = Hash::make('Agu@123456');

        $sinhViens = [
            [
                'mssv'               => 'DTH215001',
                'ho_ten'             => 'Lê Văn Minh',
                'email_sv'           => 'dth215001@student.agu.edu.vn',
                'mat_khau_mac_dinh'  => $defaultPassword,
                'ma_khoa'            => 'CNTT',
                'da_kich_hoat'       => false,
                'kich_hoat_luc'      => null,
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
            [
                'mssv'               => 'DTH215002',
                'ho_ten'             => 'Phạm Thị Thảo',
                'email_sv'           => 'dth215002@student.agu.edu.vn',
                'mat_khau_mac_dinh'  => $defaultPassword,
                'ma_khoa'            => 'CNTT',
                'da_kich_hoat'       => false,
                'kich_hoat_luc'      => null,
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
            [
                'mssv'               => 'DKT225010',
                'ho_ten'             => 'Nguyễn Hoàng Nam',
                'email_sv'           => 'dkt225010@student.agu.edu.vn',
                'mat_khau_mac_dinh'  => $defaultPassword,
                'ma_khoa'            => 'KT',
                'da_kich_hoat'       => false,
                'kich_hoat_luc'      => null,
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
        ];

        foreach ($sinhViens as $sv) {
            DB::table('danh_sach_sinh_vien_raw')->updateOrInsert(
                ['mssv' => $sv['mssv']],
                $sv
            );
        }
    }
}
