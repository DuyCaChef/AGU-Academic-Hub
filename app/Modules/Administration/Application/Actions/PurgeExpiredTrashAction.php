<?php

namespace App\Modules\Administration\Application\Actions;

use App\Models\LichSuThungRac;
use App\Models\NguoiDung;
use App\Models\TaiLieu;
use App\Modules\Administration\Application\Queries\GetStorageOverviewQuery;
use App\Modules\Administration\Domain\Enums\HanhDongKiemToan;
use App\Modules\Administration\Domain\Enums\LoaiDoiTuongKiemToan;
use App\Modules\Administration\Domain\Services\AuditLogger;
use App\Modules\Administration\Domain\ValueObjects\StorageMetric;
use App\Modules\Administration\Infrastructure\Storage\PhysicalStorageScanner;
use Illuminate\Support\Facades\DB;

class PurgeExpiredTrashAction
{
    public function __construct(
        protected PhysicalStorageScanner $scanner,
        protected GetStorageOverviewQuery $overviewQuery
    ) {}

    // Thực thi xóa vĩnh viễn các tài liệu trong thùng rác đã quá hạn lưu trữ (Two-Phase Purge: ACID CSDL -> Xóa Disk -> Xóa Cache)
    public function execute(NguoiDung $actor): array
    {
        $filesToDelete = [];
        $purgedCount = 0;
        $freedKb = 0.0;

        // Giai đoạn 1: Giao dịch CSDL nguyên tử (Atomic Database Transaction)
        DB::transaction(function () use ($actor, &$filesToDelete, &$purgedCount, &$freedKb): void {
            $now = now();
            $expiredDocs = TaiLieu::onlyTrashed()
                ->where('han_luu_thung_rac', '<=', $now)
                ->lockForUpdate()
                ->get();

            foreach ($expiredDocs as $doc) {
                $fileKb = (float) $doc->dung_luong_kb;
                $filePath = $doc->duong_dan_tep;

                // Ghi vết vào nhật ký kiểm toán Thùng rác (bắt buộc theo quy định audit AGU)
                LichSuThungRac::create([
                    'ma_tai_lieu' => $doc->getKey(),
                    'ma_nguoi_thuc_hien' => $actor->getKey(),
                    'hanh_dong' => 'PERMANENT_DELETE',
                    'nguon' => 'SYSTEM_PURGE',
                    'ly_do' => 'Hệ thống tự động xóa vĩnh viễn do đã quá hạn lưu trữ thùng rác.',
                    'chi_tiet' => [
                        'dung_luong_kb' => $fileKb,
                        'duong_dan_tep' => $filePath,
                    ],
                ]);

                // Xóa vĩnh viễn bản ghi khỏi CSDL
                $doc->forceDelete();

                // Lưu đường dẫn file vật lý để xóa sau khi transaction CSDL commit
                if ($filePath) {
                    $filesToDelete[] = $filePath;
                }

                $purgedCount++;
                $freedKb += $fileKb;
            }
        });

        // Giai đoạn 2: Xóa tệp vật lý trên đĩa sau khi CSDL đã commit thành công
        $diskName = config('storage.document_disk', 'local');
        $this->scanner->deletePhysicalFiles($filesToDelete, $diskName);

        // Giai đoạn 3: Xóa cache ngay lập tức để cập nhật số liệu mới trên Dashboard
        $this->overviewQuery->clearCache();

        if ($purgedCount > 0) {
            app(AuditLogger::class)->log(
                actor: $actor,
                action: HanhDongKiemToan::DON_DEP_THUNG_RAC,
                targetType: LoaiDoiTuongKiemToan::TAI_LIEU,
                targetId: 'EXPIRED_PURGE',
                description: "Thanh lọc vĩnh viễn {$purgedCount} tài liệu quá hạn trong thùng rác, giải phóng " . StorageMetric::formatKb($freedKb) . ".",
                oldData: null,
                newData: [
                    'so_tai_lieu_da_xoa' => $purgedCount,
                    'dung_luong_giai_phong_kb' => round($freedKb, 2),
                ]
            );
        }

        return [
            'so_tai_lieu_da_xoa' => $purgedCount,
            'dung_luong_giai_phong_kb' => round($freedKb, 2),
            'dung_luong_giai_phong_dinh_dang' => StorageMetric::formatKb($freedKb),
        ];
    }
}

