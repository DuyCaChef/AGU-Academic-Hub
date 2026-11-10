<?php

namespace App\Modules\Bounties\Application\Actions;

use App\Enums\TrangThaiYeuCauTaiLieu;
use App\Models\NguoiDung;
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

        $yeuCau = $this->bountyRepository->getPendingPayoutDetail($maYeuCau);

        // Yêu cầu phải đang ở trạng thái CHO_GIAI_NGAN
        if ($yeuCau->trang_thai !== TrangThaiYeuCauTaiLieu::CHO_GIAI_NGAN) {
            return [
                'success' => false,
                'message' => 'Yêu cầu này không ở trạng thái chờ duyệt giải ngân.',
            ];
        }

        $dapUng = $yeuCau->dapUngDuocChon;
        if (!$dapUng) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy bài đóng góp được chọn để giải ngân.',
            ];
        }

        $nguoiDongGop = $dapUng->nguoiDongGop;

        DB::transaction(function () use ($yeuCau, $dapUng, $actor, $nguoiDongGop) {
            // Giải ngân điểm thưởng qua ReputationEvaluator
            $this->reputationEvaluator->giaiNganDiemBounty($yeuCau, $nguoiDongGop);

            // Cập nhật trạng thái bài nộp sang CHAP_NHAN và đóng yêu cầu DA_HOAN_THANH
            $this->bountyRepository->approvePayout($yeuCau, $dapUng, $actor->ma_nguoi_dung);

            // Ghi nhật ký kiểm toán về hành động duyệt giải ngân
            $this->auditLogger->log(
                $actor,
                HanhDongKiemToan::DUYET_GIAI_NGAN_BOUNTY,
                LoaiDoiTuongKiemToan::YEU_CAU_TAI_LIEU,
                $yeuCau->ma_yeu_cau,
                "Duyệt giải ngân {$yeuCau->diem_thuong} điểm Bounty cho {$nguoiDongGop->ho_ten} - Yêu cầu #{$yeuCau->ma_yeu_cau}",
                ['trang_thai' => TrangThaiYeuCauTaiLieu::CHO_GIAI_NGAN->value],
                ['trang_thai' => TrangThaiYeuCauTaiLieu::DA_HOAN_THANH->value]
            );
        });

        // Kích hoạt sự kiện hoàn tất giải ngân cho Notification Module (UC-ST-04, UC-ST-05)
        BountyFulfillmentConfirmedEvent::dispatch(
            $yeuCau,
            $dapUng,
            $yeuCau->nguoiTao,
            $yeuCau->diem_thuong
        );

        return [
            'success' => true,
            'message' => "Đã duyệt giải ngân thành công! {$nguoiDongGop->ho_ten} nhận được {$yeuCau->diem_thuong} điểm thưởng.",
        ];
    }
}
