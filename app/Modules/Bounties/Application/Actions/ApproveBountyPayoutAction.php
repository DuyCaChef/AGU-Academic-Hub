<?php

namespace App\Modules\Bounties\Application\Actions;

use App\Enums\TrangThaiYeuCauTaiLieu;
use App\Models\DapUngYeuCauTaiLieu;
use App\Models\NguoiDung;
use App\Models\YeuCauTaiLieu;
use App\Modules\Administration\Domain\Enums\HanhDongKiemToan;
use App\Modules\Administration\Domain\Enums\LoaiDoiTuongKiemToan;
use App\Modules\Administration\Domain\Services\AuditLogger;
use App\Modules\Bounties\Domain\Services\ReputationEvaluator;
use App\Modules\Bounties\Infrastructure\Notifications\BountyPayoutApprovedNotification;
use App\Modules\Bounties\Infrastructure\Persistence\WishlistBountyRepository;
use App\Modules\Notifications\Domain\Events\BountyFulfillmentConfirmedEvent;
use Illuminate\Support\Facades\DB;

// Action cho Admin/Mod duyệt giải ngân điểm thưởng Bounty sau khi người yêu cầu xác nhận
class ApproveBountyPayoutAction
{
    public function __construct(
        protected WishlistBountyRepository $bountyRepository,
        protected ReputationEvaluator $reputationEvaluator,
        protected AuditLogger $auditLogger
    ) {}

    public function execute(NguoiDung $actor, int $maYeuCau): array
    {
        // Chỉ Admin hoặc Kiểm duyệt viên mới có quyền duyệt giải ngân
        if (!$actor->laQuanTriVien() && !$actor->laKiemDuyetVien()) {
            abort(403, 'Bạn không có quyền duyệt giải ngân điểm Bounty.');
        }

        return DB::transaction(function () use ($actor, $maYeuCau) {
            // 1. Khóa bi quan bản ghi Bounty (Pessimistic Locking)
            $yeuCau = YeuCauTaiLieu::where('ma_yeu_cau', $maYeuCau)->lockForUpdate()->first();
            if (!$yeuCau || $yeuCau->trang_thai !== TrangThaiYeuCauTaiLieu::CHO_GIAI_NGAN) {
                return [
                    'success' => false,
                    'message' => 'Yêu cầu này không ở trạng thái chờ duyệt giải ngân hoặc đã được xử lý bởi quản trị viên khác.',
                ];
            }

            if (!$yeuCau->ma_dap_ung_duoc_chon) {
                return [
                    'success' => false,
                    'message' => 'Không tìm thấy bài đóng góp được chọn để giải ngân.',
                ];
            }

            // 2. Khóa bi quan bản ghi bài nộp đóng góp
            $dapUng = DapUngYeuCauTaiLieu::where('ma_dap_ung', $yeuCau->ma_dap_ung_duoc_chon)->lockForUpdate()->first();
            if (!$dapUng || $dapUng->trang_thai !== 'NGUOI_YEU_CAU_XAC_NHAN') {
                return [
                    'success' => false,
                    'message' => 'Bài đóng góp không ở trạng thái hợp lệ để giải ngân.',
                ];
            }

            $nguoiDongGop = $dapUng->nguoiDongGop;
            $nguoiYeuCau  = $yeuCau->nguoiTao;

            // 3. Giải ngân điểm thưởng qua ReputationEvaluator (khóa ví 2 bên và check idempotency)
            $this->reputationEvaluator->giaiNganDiemBounty($yeuCau, $nguoiDongGop);

            // 4. Cập nhật trạng thái bài nộp sang CHAP_NHAN và đóng yêu cầu DA_HOAN_THANH
            $this->bountyRepository->approvePayout($yeuCau, $dapUng, $actor->ma_nguoi_dung);

            // 5. Ghi nhật ký kiểm toán về hành động duyệt giải ngân
            $this->auditLogger->log(
                $actor,
                HanhDongKiemToan::DUYET_GIAI_NGAN_BOUNTY,
                LoaiDoiTuongKiemToan::YEU_CAU_TAI_LIEU,
                $yeuCau->ma_yeu_cau,
                "Duyệt giải ngân {$yeuCau->diem_thuong} điểm Bounty cho {$nguoiDongGop->ho_ten} - Yêu cầu #{$yeuCau->ma_yeu_cau}",
                ['trang_thai' => TrangThaiYeuCauTaiLieu::CHO_GIAI_NGAN->value],
                ['trang_thai' => TrangThaiYeuCauTaiLieu::DA_HOAN_THANH->value]
            );

            // 6. Phát sự kiện hoàn tất giải ngân sau khi commit transaction an toàn
            DB::afterCommit(function () use ($yeuCau, $dapUng, $nguoiYeuCau) {
                BountyFulfillmentConfirmedEvent::dispatch(
                    $yeuCau,
                    $dapUng,
                    $nguoiYeuCau,
                    $yeuCau->diem_thuong
                );
            });

            return [
                'success' => true,
                'message' => "Đã duyệt giải ngân thành công! {$nguoiDongGop->ho_ten} nhận được {$yeuCau->diem_thuong} điểm thưởng.",
            ];
        });
    }
}
