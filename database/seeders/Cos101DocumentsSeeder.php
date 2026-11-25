<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class Cos101DocumentsSeeder extends Seeder
{
    public function run(): void
    {
        $monHoc = DB::table('mon_hoc')->where('ma_hoc_phan', 'COS101')->first();
        if (! $monHoc) {
            $this->command->error('Không tìm thấy môn học COS101 trong bảng mon_hoc.');
            return;
        }

        $monHocId = $monHoc->ma_mon_hoc ?? $monHoc->id;
        $user = DB::table('nguoi_dung')->first();
        $userId = $user->ma_nguoi_dung ?? $user->id;

        // Tạo file PDF mẫu hợp lệ trên private storage
        $pdfDir = storage_path('app/private/documents');
        File::ensureDirectoryExists($pdfDir);
        $pdfPath = $pdfDir . '/cos101_sample.pdf';
        if (! File::exists($pdfPath)) {
            $minimalPdf = "%PDF-1.4\n1 0 obj<< /Type /Catalog /Pages 2 0 R >>endobj\n2 0 obj<< /Type /Pages /Kids [3 0 R] /Count 1 >>endobj\n3 0 obj<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] >>endobj\nxref\n0 4\n0000000000 65535 f \n0000000009 00000 n \n0000000058 00000 n \n0000000115 00000 n \ntrailer<< /Size 4 /Root 1 0 R >>\nstartxref\n183\n%%EOF";
            File::put($pdfPath, $minimalPdf);
        }

        // Đọc cấu trúc cột thực tế từ MySQL
        $tableCols = collect(DB::select('SHOW COLUMNS FROM tai_lieu'))->keyBy('Field');

        $parseEnum = function (?object $col): array {
            if (! $col || ! str_starts_with(strtolower($col->Type), 'enum')) {
                return [];
            }
            preg_match_all("/'([^']+)'/", $col->Type, $matches);
            return $matches[1] ?? [];
        };

        // Xác định giá trị trạng thái đã duyệt
        $statusEnums = $parseEnum($tableCols->get('trang_thai'));
        $approvedStatus = in_array('DA_DUYET', $statusEnums, true)
            ? 'DA_DUYET'
            : (in_array('PUBLISHED', $statusEnums, true) ? 'PUBLISHED' : ($statusEnums[0] ?? 'DA_DUYET'));

        // Xác định cột phân loại tài liệu và danh sách giá trị hợp lệ
        $catColName = collect(['loai_tai_lieu', 'phan_loai', 'danh_muc', 'loai'])
            ->first(fn ($c) => $tableCols->has($c));
        $catEnums = $catColName ? $parseEnum($tableCols->get($catColName)) : [];
        $categories = ! empty($catEnums) ? $catEnums : ['DE_THI', 'BAI_GIANG', 'DO_AN', 'BAI_TAP'];

        $labelMap = [
            'DE_THI' => 'Đề thi',
            'EXAM' => 'Đề thi',
            'BAI_GIANG' => 'Bài giảng',
            'LECTURE' => 'Bài giảng',
            'GIAO_TRINH' => 'Giáo trình',
            'DO_AN' => 'Đồ án',
            'PROJECT' => 'Đồ án',
            'BAI_TAP' => 'Bài tập',
            'EXERCISE' => 'Bài tập',
            'TAI_LIEU_THAM_KHAO' => 'Tài liệu tham khảo',
            'KHAC' => 'Tài liệu khác',
        ];

        for ($i = 1; $i <= 18; $i++) {
            $catVal = $categories[($i - 1) % count($categories)];
            $label = $labelMap[$catVal] ?? 'Tài liệu';

            $candidates = [
                'ma_mon_hoc' => $monHocId,
                'ma_nguoi_dang' => $userId,
                'ma_nguoi_tai_len' => $userId,
                'ma_nguoi_dung' => $userId,
                'tieu_de' => "[{$label}] Tài liệu học phần Nhập môn Lập trình COS101 - Phần {$i}",
                'mo_ta' => "Tài liệu ôn tập và thực hành môn COS101 (dữ liệu kiểm thử phân trang số {$i}).",
                'ma_bam_file' => hash('sha256', "cos101_seed_doc_{$i}_" . microtime(true)),
                'ten_tep_goc' => "cos101_tai_lieu_{$i}.pdf",
                'ten_file_goc' => "cos101_tai_lieu_{$i}.pdf",
                'o_dia_luu_tru' => 'private',
                'duong_dan_tep' => 'documents/cos101_sample.pdf',
                'phan_mo_rong' => 'pdf',
                'dinh_dang' => 'pdf',
                'loai_mime' => 'application/pdf',
                'kich_thuoc_tep' => 1048576 + ($i * 20480),
                'kich_thuoc_file' => 1048576 + ($i * 20480),
                'kich_thuoc_bytes' => 1048576 + ($i * 20480),
                'dung_luong_bytes' => 1048576 + ($i * 20480),
                'phien_ban' => '1.0',
                'luot_tai' => $i % 6,
                'luot_thich' => $i % 4,
                'trang_thai' => $approvedStatus,
                'created_at' => now()->subMinutes(30 - $i),
                'updated_at' => now()->subMinutes(30 - $i),
            ];

            if ($catColName) {
                $candidates[$catColName] = $catVal;
            }

            $row = [];
            foreach ($candidates as $col => $val) {
                if ($tableCols->has($col)) {
                    $row[$col] = $val;
                }
            }

            // Điền giá trị mặc định an toàn nếu còn cột NOT NULL chưa có default
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
                        $row[$field] = 'N/A';
                    }
                }
            }

            DB::table('tai_lieu')->insert($row);
        }

        $this->command->info('Đã nạp thành công 18 tài liệu mẫu cho môn COS101!');
    }
}
