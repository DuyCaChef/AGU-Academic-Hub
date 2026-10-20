<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MonHocSeeder extends Seeder
{
    public function run(): void
    {
        $khoaCntt = DB::table('khoa')
            ->where('ky_hieu_khoa', 'CNTT')
            ->value('ma_khoa');

        $dth = DB::table('chuyen_nganh')
            ->where('ky_hieu_nganh', 'DTH')
            ->value('ma_chuyen_nganh');

        $dpm = DB::table('chuyen_nganh')
            ->where('ky_hieu_nganh', 'DPM')
            ->value('ma_chuyen_nganh');

        if (!$khoaCntt || !$dth || !$dpm) {
            throw new RuntimeException(
                'Hãy chạy KhoaSeeder và ChuyenNganhSeeder trước.'
            );
        }

        $monHocs = [
            [
                'ma_hoc_phan' => 'COS101',
                'ten_mon_hoc' => 'Lập trình căn bản',
                'so_tin_chi' => 3,
                'mo_ta' => 'Nhập môn lập trình và tư duy giải quyết vấn đề.',
                'chuyen_nganhs' => [$dth, $dpm],
            ],
            [
                'ma_hoc_phan' => 'COS201',
                'ten_mon_hoc' => 'Cấu trúc dữ liệu và giải thuật',
                'so_tin_chi' => 3,
                'mo_ta' => 'Các cấu trúc dữ liệu và thuật toán cơ bản.',
                'chuyen_nganhs' => [$dth, $dpm],
            ],
            [
                'ma_hoc_phan' => 'COS301',
                'ten_mon_hoc' => 'Cơ sở dữ liệu',
                'so_tin_chi' => 3,
                'mo_ta' => 'Mô hình dữ liệu, SQL và thiết kế cơ sở dữ liệu.',
                'chuyen_nganhs' => [$dth, $dpm],
            ],
            [
                'ma_hoc_phan' => 'COS401',
                'ten_mon_hoc' => 'Phát triển ứng dụng web',
                'so_tin_chi' => 3,
                'mo_ta' => 'Phát triển ứng dụng web và tích hợp API.',
                'chuyen_nganhs' => [$dth, $dpm],
            ],
        ];

        foreach ($monHocs as $item) {
            $chuyenNganhs = $item['chuyen_nganhs'];
            unset($item['chuyen_nganhs']);

            DB::table('mon_hoc')->updateOrInsert(
                ['ma_hoc_phan' => $item['ma_hoc_phan']],
                $item + [
                    'ma_khoa' => $khoaCntt,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $maMonHoc = DB::table('mon_hoc')
                ->where('ma_hoc_phan', $item['ma_hoc_phan'])
                ->value('ma_mon_hoc');

            foreach ($chuyenNganhs as $maChuyenNganh) {
                DB::table('mon_hoc_chuyen_nganh')->updateOrInsert(
                    [
                        'ma_mon_hoc' => $maMonHoc,
                        'ma_chuyen_nganh' => $maChuyenNganh,
                    ],
                    [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }
}