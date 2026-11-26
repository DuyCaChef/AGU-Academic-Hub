<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TaiLieuSeeder extends Seeder
{
    public function run(): void
    {
        $maNguoiDang = DB::table('nguoi_dung')
            ->where('email', 'sinhvien@agu.edu.vn')
            ->value('ma_nguoi_dung');

        $maMonHoc = DB::table('mon_hoc')
            ->where('ma_hoc_phan', 'COS301')
            ->value('ma_mon_hoc');

        if (!$maNguoiDang || !$maMonHoc) {
            throw new RuntimeException(
                'Hãy chạy NguoiDungSeeder và MonHocSeeder trước.'
            );
        }

        $taiLieus = [
            [
                'tieu_de' => 'Tổng hợp bài giảng Cơ sở dữ liệu',
                'duong_dan_tep' => 'demo/tong-hop-co-so-du-lieu.pdf',
                'dinh_dang_tep' => 'pdf',
                'dung_luong_kb' => 2048,
                'phan_loai' => 'BAI_GIANG',
                'luot_tai' => 25,
                'luot_thich' => 8,
            ],
            [
                'tieu_de' => 'Đề thi tham khảo Cơ sở dữ liệu',
                'duong_dan_tep' => 'demo/de-thi-co-so-du-lieu.pdf',
                'dinh_dang_tep' => 'pdf',
                'dung_luong_kb' => 1536,
                'phan_loai' => 'DE_THI',
                'luot_tai' => 18,
                'luot_thich' => 6,
            ],
            [
                'tieu_de' => 'Bài tập SQL có lời giải',
                'duong_dan_tep' => 'demo/bai-tap-sql.pdf',
                'dinh_dang_tep' => 'pdf',
                'dung_luong_kb' => 1024,
                'phan_loai' => 'BAI_TAP',
                'luot_tai' => 12,
                'luot_thich' => 4,
            ],
        ];

        foreach ($taiLieus as $item) {
            $hash = hash('sha256', $item['duong_dan_tep']);

            DB::table('tai_lieu')->updateOrInsert(
                ['ma_bam_file' => $hash],
                $item + [
                    'ma_tai_lieu_goc' => null,
                    'ma_mon_hoc' => $maMonHoc,
                    'ma_nguoi_dang' => $maNguoiDang,
                    'ma_bam_file' => $hash,
                    'chu_ky_noi_dung' => null,
                    'phien_ban' => '1.0',
                    'trang_thai' => 'DA_DUYET',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}