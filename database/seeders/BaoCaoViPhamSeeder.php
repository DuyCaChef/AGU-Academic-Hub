<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class BaoCaoViPhamSeeder extends Seeder
{
    public function run(): void
    {
        $maKhoa = DB::table('khoa')->value('ma_khoa');
        $documents = DB::table('tai_lieu')
            ->whereNull('ngay_xoa_tam')
            ->orderBy('ma_tai_lieu')
            ->pluck('ma_tai_lieu');

        if (! $maKhoa || $documents->isEmpty()) {
            throw new RuntimeException('Hãy chạy các seeder khoa và tài liệu trước.');
        }

        $reporters = [
            [
                'email' => 'baocao1@agu.edu.vn',
                'ho_ten' => 'Lê Minh Châu',
                'mssv' => 'DTH225101',
            ],
            [
                'email' => 'baocao2@agu.edu.vn',
                'ho_ten' => 'Phạm Quốc Huy',
                'mssv' => 'DTH225102',
            ],
            [
                'email' => 'baocao3@agu.edu.vn',
                'ho_ten' => 'Võ Ngọc Mai',
                'mssv' => 'DTH225103',
            ],
        ];

        $reporterIds = [];
        foreach ($reporters as $reporter) {
            DB::table('nguoi_dung')->updateOrInsert(
                ['email' => $reporter['email']],
                $reporter + [
                    'ma_khoa' => $maKhoa,
                    'mat_khau' => Hash::make('Demo@123456'),
                    'vai_tro' => 'SINH_VIEN',
                    'trang_thai' => 'DANG_HOAT_DONG',
                    'diem_uy_tin' => 100,
                    'xac_thuc_email_luc' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );

            $reporterIds[] = DB::table('nguoi_dung')
                ->where('email', $reporter['email'])
                ->value('ma_nguoi_dung');
        }

        $reports = [
            [
                'document' => 0,
                'reporter' => 0,
                'reason' => 'BAN_QUYEN',
                'detail' => 'Tài liệu có nội dung giống tài liệu đã được công bố trước đó và chưa thấy thông tin nguồn.',
                'daysAgo' => 0,
            ],
            [
                'document' => 0,
                'reporter' => 1,
                'reason' => 'SAI_NOI_DUNG',
                'detail' => 'Tiêu đề ghi là bài giảng Cơ sở dữ liệu nhưng nội dung tải xuống không đúng môn học.',
                'daysAgo' => 2,
            ],
            [
                'document' => 0,
                'reporter' => 2,
                'reason' => 'TRUNG_LAP',
                'detail' => 'Tài liệu trùng với một tài liệu khác đã có trong kho.',
                'daysAgo' => 5,
            ],
            [
                'document' => min(1, $documents->count() - 1),
                'reporter' => 0,
                'reason' => 'MA_DOC',
                'detail' => 'File tải về có dấu hiệu bất thường, đề nghị kiểm tra trước khi cho phép tải tiếp.',
                'daysAgo' => 8,
            ],
            [
                'document' => min(1, $documents->count() - 1),
                'reporter' => 1,
                'reason' => 'KHAC',
                'detail' => 'Nội dung có phần chú thích không phù hợp với mục đích chia sẻ học thuật.',
                'daysAgo' => 12,
            ],
        ];

        foreach ($reports as $report) {
            $documentId = $documents[$report['document']];
            $reporterId = $reporterIds[$report['reporter']];
            $createdAt = now()->subDays($report['daysAgo']);

            $exists = DB::table('bao_cao_vi_pham')
                ->where('ma_tai_lieu', $documentId)
                ->where('ma_nguoi_bao_cao', $reporterId)
                ->where('ly_do', $report['reason'])
                ->exists();

            if (! $exists) {
                DB::table('bao_cao_vi_pham')->insert([
                    'ma_tai_lieu' => $documentId,
                    'ma_nguoi_bao_cao' => $reporterId,
                    'ly_do' => $report['reason'],
                    'chi_tiet_loi' => $report['detail'],
                    'trang_thai_xu_ly' => 'CHO_XU_LY',
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }
        }
    }
}
