<?php

namespace App\Modules\Bounties\Application\Actions;

use App\Enums\TrangThaiYeuCauTaiLieu;
use App\Models\NguoiDung;
use App\Modules\Administration\Domain\Enums\HanhDongKiemToan;
use App\Modules\Administration\Domain\Enums\LoaiDoiTuongKiemToan;
use App\Modules\Administration\Domain\Services\AuditLogger;
use App\Modules\Bounties\Domain\Services\ReputationEvaluator;
use App\Modules\Bounties\Infrastructure\Notifications\BountyPayoutRejectedNotification;
use App\Modules\Bounties\Infrastructure\Persistence\WishlistBountyRepository;
use Illuminate\Support\Facades\DB;

// Action cho Admin/Mod từ chối giải ngân - hoàn điểm cho người yêu cầu và mở lại yêu cầu
class RejectBountyPayoutAction
{
    public function __construct(
        protected WishlistBountyRepository $bountyRepository,
        protected ReputationEvaluator $reputationEvaluator,
        protected AuditLogger $auditLogger
    ) {}

    public function execute(NguoiDung $actor, int $maYeuCau, string $lyDo): array
    {
        // Chỉ Admin hoặc Kiểm duyệt viên mới có quyền từ chối giải ngân
        if (!$actor->laQuanTriVien() && !$actor->laKiemDuyetVien()) {
            abort(403, 'Bạn không có quyền từ chối giải ngân điểm Bounty.');
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
                'message' => 'Không tìm thấy bài đóng góp được chọn để từ chối.',
            ];
        }

        $nguoiDongGop = $dapUng->nguoiDongGop;
        $nguoiYeuCau  = $yeuCau->nguoiTao;

        DB::transaction(function () use ($yeuCau, $dapUng, $actor, $lyDo) {
            // Hoàn trả điểm tạm giữ về ví khả dụng của người yêu cầu
            $this->reputationEvaluator->hoanDiemBounty($yeuCau, "tu_choi_giai_ngan_{$yeuCau->ma_yeu_cau}");

            // Đánh dấu bài nộp TU_CHOI và mở lại yêu cầu sang MO
            $this->bountyRepository->rejectPayout($yeuCau, $dapUng, $lyDo, $actor->ma_nguoi_dung);

            // Ghi nhật ký kiểm toán về hành động từ chối giải ngân
            $this->auditLogger->log(
                $actor,
                HanhDongKiemToan::TU_CHOI_GIAI_NGAN_BOUNTY,
                LoaiDoiTuongKiemToan::YEU_CAU_TAI_LIEU,
                $yeuCau->ma_yeu_cau,
                "Từ chối giải ngân Bounty - Yêu cầu #{$yeuCau->ma_yeu_cau}. Lý do: {$lyDo}",
                ['trang_thai' => TrangThaiYeuCauTaiLieu::CHO_GIAI_NGAN->value],
                ['trang_thai' => TrangThaiYeuCauTaiLieu::MO->value, 'ly_do' => $lyDo]
            );
        });

        // Thông báo cho người yêu cầu - điểm đã được hoàn trả, yêu cầu mở lại
        $nguoiYeuCau->notify(new BountyPayoutRejectedNotification(
            maYeuCau:   $yeuCau->ma_yeu_cau,
            diemThuong: $yeuCau->diem_thuong,
            lyDo:       $lyDo,
            recipient:  'nguoi_yeu_cau'
        ));

        // Thông báo cho người đóng góp về lý do bị từ chối
        $nguoiDongGop->notify(new BountyPayoutRejectedNotification(
            maYeuCau:   $yeuCau->ma_yeu_cau,
            diemThuong: $yeuCau->diem_thuong,
            lyDo:       $lyDo,
            recipient:  'nguoi_dong_gop'
        ));

        return [
            'success' => true,
            'message' => "Đã từ chối giải ngân. Điểm thưởng {$yeuCau->diem_thuong} điểm đã hoàn trả về ví của người yêu cầu. Yêu cầu đã được mở lại.",
        ];
    }
}
