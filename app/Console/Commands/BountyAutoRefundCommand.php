<?php

namespace App\Console\Commands;

use App\Enums\TrangThaiYeuCauTaiLieu;
use App\Models\YeuCauTaiLieu;
use App\Services\Wallet\WalletLedgerEvaluator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Command tự động hủy và hoàn trả điểm cọc cho các yêu cầu Bounty quá hạn 30 ngày
// Điều kiện hủy: trạng thái MO, tạo > 30 ngày trước, không có bài nộp đang chờ xử lý
class BountyAutoRefundCommand extends Command
{
    // Chữ ký lệnh artisan
    protected $signature = 'bounty:auto-refund
                            {--days=30 : Số ngày không có bài nộp trước khi tự động hủy}
                            {--dry-run : Chạy thử không ghi dữ liệu}';

    // Mô tả lệnh
    protected $description = 'Tự động hủy và hoàn trả điểm cọc cho các yêu cầu Bounty quá hạn không có bài nộp hợp lệ';

    // Xử lý chính của command
    public function handle(WalletLedgerEvaluator $walletEvaluator): int
    {
        $days = (int) $this->option('days');
        $isDryRun = (bool) $this->option('dry-run');

        $this->info("// Quét yêu cầu Bounty quá {$days} ngày không có bài nộp hợp lệ...");

        // Trạng thái bài nộp cần loại trừ: đang chờ người yêu cầu xác nhận hoặc chờ duyệt
        // (dap_ung_yeu_cau_tai_lieu.trang_thai: CHO_DUYET | NGUOI_YEU_CAU_XAC_NHAN | CHAP_NHAN | TU_CHOI)
        $pendingStatuses = ['CHO_DUYET', 'NGUOI_YEU_CAU_XAC_NHAN'];

        // Tìm các yêu cầu Bounty quá hạn, trạng thái MO, không có bài nộp đang xử lý
        $expiredRequests = YeuCauTaiLieu::withoutTrashed()
            ->where('trang_thai', TrangThaiYeuCauTaiLieu::MO)
            ->where('created_at', '<', now()->subDays($days))
            ->whereDoesntHave('dapUngs', function ($q) use ($pendingStatuses) {
                // Loại trừ nếu còn bài nộp đang chờ người yêu cầu xác nhận
                $q->whereIn('trang_thai', $pendingStatuses);
            })
            ->get();

        $totalFound = $expiredRequests->count();
        $totalProcessed = 0;
        $totalRefunded = 0;

        $this->line("// Tìm thấy {$totalFound} yêu cầu cần xử lý.");

        if ($isDryRun) {
            $this->warn('// [DRY-RUN] Chế độ thử nghiệm — không ghi dữ liệu thực.');
        }

        foreach ($expiredRequests as $yeuCau) {
            $diemThuong = $yeuCau->diem_thuong ?? 0;

            if ($isDryRun) {
                // Chế độ dry-run: chỉ in thông tin, không xử lý thực tế
                $this->line("// [DRY-RUN] Sẽ hủy yêu cầu #{$yeuCau->ma_yeu_cau} — hoàn {$diemThuong} điểm cho sinh viên #{$yeuCau->ma_nguoi_tao}");
                $totalProcessed++;
                $totalRefunded += $diemThuong;
                continue;
            }

            try {
                DB::transaction(function () use ($yeuCau, $walletEvaluator, $diemThuong, &$totalRefunded) {
                    // Khóa chống trùng lặp idempotency per yêu cầu
                    $khoaChongLap = "bounty_auto_refund_yc_{$yeuCau->ma_yeu_cau}";

                    // Hoàn trả điểm cọc về ví khả dụng của người tạo yêu cầu
                    $walletEvaluator->hoanDiemBounty($yeuCau, $khoaChongLap);

                    // Chuyển sang trạng thái DA_HUY
                    $yeuCau->trang_thai = TrangThaiYeuCauTaiLieu::DA_HUY;
                    $yeuCau->save();

                    // Soft delete yêu cầu
                    $yeuCau->delete();

                    $totalRefunded += $diemThuong;
                });

                $this->line("// Đã hoàn điểm yêu cầu #{$yeuCau->ma_yeu_cau} ({$diemThuong} điểm)");
                $totalProcessed++;
            } catch (\Throwable $e) {
                // Ghi log lỗi nhưng tiếp tục xử lý các yêu cầu còn lại
                Log::error("BountyAutoRefundCommand: Lỗi xử lý yêu cầu #{$yeuCau->ma_yeu_cau}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $this->error("// Lỗi yêu cầu #{$yeuCau->ma_yeu_cau}: {$e->getMessage()}");
            }
        }

        $this->info("// Hoàn thành: {$totalProcessed}/{$totalFound} yêu cầu, hoàn tổng {$totalRefunded} điểm.");

        return Command::SUCCESS;
    }
}
