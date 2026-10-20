<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ChuyenNganhSeeder extends Seeder
{
    public function run(): void
    {
        $khoaCntt = DB::table('khoa')
            ->where('ky_hieu_khoa', 'CNTT')
            ->value('ma_khoa');

        $khoaKt = DB::table('khoa')
            ->where('ky_hieu_khoa', 'KT')
            ->value('ma_khoa');

        if (!$khoaCntt || !$khoaKt) {
            throw new RuntimeException('Hãy chạy KhoaSeeder trước.');
        }

        $chuyenNganhs = [
            [
                'ky_hieu_nganh' => 'DTH',
                'ten_chuyen_nganh' => 'Công nghệ thông tin',
                'ma_khoa' => $khoaCntt,
                'mo_ta' => 'Đào tạo về lập trình, cơ sở dữ liệu và phát triển phần mềm.',
            ],
            [
                'ky_hieu_nganh' => 'DPM',
                'ten_chuyen_nganh' => 'Kỹ thuật phần mềm',
                'ma_khoa' => $khoaCntt,
                'mo_ta' => 'Tập trung vào quy trình và kỹ thuật phát triển phần mềm.',
            ],
            [
                'ky_hieu_nganh' => 'QTKD',
                'ten_chuyen_nganh' => 'Quản trị kinh doanh',
                'ma_khoa' => $khoaKt,
                'mo_ta' => 'Đào tạo kiến thức quản trị và kinh doanh.',
            ],
        ];

        foreach ($chuyenNganhs as $item) {
            DB::table('chuyen_nganh')->updateOrInsert(
                ['ky_hieu_nganh' => $item['ky_hieu_nganh']],
                $item + [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}