<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DocumentRemovalRequestSeeder extends Seeder
{
    public function run(): void
    {
        $tableName = Schema::hasTable('yeu_cau_go_bo_tai_lieu')
            ? 'yeu_cau_go_bo_tai_lieu'
            : (Schema::hasTable('yeu_cau_go_tai_lieu') ? 'yeu_cau_go_tai_lieu' : null);

        if (! $tableName) {
            $this->command->error('Không tìm thấy bảng yeu_cau_go_bo_tai_lieu trong CSDL.');
            return;
        }

        // 1. Lấy các tài liệu đang hiển thị chưa có đơn xin gỡ chờ xử lý
        $pendingDocIds = DB::table($tableName)
            ->where('trang_thai', 'CHO_XU_LY')
            ->pluck('ma_tai_lieu')
            ->toArray();

        $docs = DB::table('tai_lieu')
            ->whereNull('ngay_xoa_tam')
            ->where('trang_thai', 'DA_DUYET')
            ->whereNotIn('ma_tai_lieu', $pendingDocIds)
            ->orderByDesc('luot_tai')
            ->take(3)
            ->get();

        if ($docs->isEmpty()) {
            $this->command->error('Không có tài liệu DA_DUYET nào khả dụng để tạo yêu cầu gỡ.');
            return;
        }

        $tableCols = collect(DB::select("SHOW COLUMNS FROM {$tableName}"))->keyBy('Field');

        $reasons = [
            'Đề cương học phần đã được Bộ môn cập nhật theo chương trình đào tạo mới, xin gỡ bản cũ để tránh sinh viên ôn nhầm nội dung.',
            'Tài liệu có một số công thức và đáp án mẫu ở chương 3 chưa chính xác, tác giả xin gỡ bỏ để rà soát và tải lên phiên bản hoàn chỉnh hơn.',
            'Giảng viên phụ trách học phần đề nghị thu hồi bộ slide lưu hành nội bộ này khỏi kho chia sẻ công khai.',
        ];

        $downloadCounts = [12, 18, 25];
        $created = 0;

        foreach ($docs as $index => $doc) {
            // Đảm bảo tài liệu đạt mốc Community Ownership (>= 10 lượt tải)
            $currentDownloads = (int) ($doc->luot_tai ?? 0);
            $targetDownloads = $currentDownloads >= 10 ? $currentDownloads : $downloadCounts[$index % count($downloadCounts)];

            if ($currentDownloads < 10) {
                DB::table('tai_lieu')
                    ->where('ma_tai_lieu', $doc->ma_tai_lieu)
                    ->update([
                        'luot_tai'   => $targetDownloads,
                        'updated_at' => now(),
                    ]);
            }

            $candidates = [
                'ma_tai_lieu'      => $doc->ma_tai_lieu,
                'ma_nguoi_yeu_cau' => $doc->ma_nguoi_dang,
                'ly_do_yeu_cau'    => $reasons[$index % count($reasons)],
                'trang_thai'       => 'CHO_XU_LY',
                'created_at'       => now()->subMinutes(15 - ($index * 4)),
                'updated_at'       => now()->subMinutes(15 - ($index * 4)),
            ];

            $row = [];
            foreach ($candidates as $col => $val) {
                if ($tableCols->has($col)) {
                    $row[$col] = $val;
                }
            }

            // Bổ sung giá trị mặc định nếu bảng có cột NOT NULL khác chưa có default
            foreach ($tableCols as $field => $meta) {
                if (! array_key_exists($field, $row)
                    && $meta->Null === 'NO'
                    && $meta->Default === null
                    && ! str_contains($meta->Extra, 'auto_increment')
                ) {
                    $type = strtolower($meta->Type);
                    if (str_contains($type, 'int') || str_contains($type, 'decimal')) {
                        $row[$field] = 0;
                    } elseif (str_contains($type, 'timestamp') || str_contains($type, 'datetime')) {
                        $row[$field] = now();
                    } else {
                        $row[$field] = 'Mac_dinh';
                    }
                }
            }

            DB::table($tableName)->insert($row);
            $created++;
            $this->command->line("- Đã tạo yêu cầu gỡ cho tài liệu #{$doc->ma_tai_lieu} ({$doc->tieu_de}) - {$targetDownloads} lượt tải.");
        }

        $this->command->info("Hoàn tất nạp {$created} yêu cầu gỡ tài liệu cộng đồng (>= 10 lượt tải)!");
    }
}
