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
            // Cập nhật trạng thái tài liệu sang TU_CHOI nếu tài liệu chưa duyệt
            if ($dapUng->taiLieu && $dapUng->taiLieu->trang_thai === 'CHO_DUYET') {
                $dapUng->taiLieu->update([
                    'trang_thai'        => 'TU_CHOI',
                    'ly_do_trang_thai' => "Bị Ban kiểm duyệt từ chối: {$lyDo}",
                ]);
            }

            // Đánh dấu bài nộp TU_CHOI và mở lại yêu cầu sang MO (không hoàn điểm khả dụng, điểm vẫn nằm trong quỹ tạm giữ của A)
            $this->bountyRepository->rejectPayout($yeuCau, $dapUng, $lyDo, $actor->ma_nguoi_dung);

            // Ghi nhật ký kiểm toán về hành động từ chối
            $this->auditLogger->log(
                $actor,
                HanhDongKiemToan::TU_CHOI_GIAI_NGAN_BOUNTY,
                LoaiDoiTuongKiemToan::YEU_CAU_TAI_LIEU,
                $yeuCau->ma_yeu_cau,
                "Từ chối tài liệu Bounty - Yêu cầu #{$yeuCau->ma_yeu_cau}. Lý do: {$lyDo}",
                ['trang_thai' => TrangThaiYeuCauTaiLieu::CHO_GIAI_NGAN->value],
                ['trang_thai' => TrangThaiYeuCauTaiLieu::MO->value, 'ly_do' => $lyDo]
            );
        });

        // Thông báo cho người yêu cầu - yêu cầu mở lại để nhận tài liệu khác
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
            'message' => "Đã từ chối bài đóng góp. Điểm thưởng {$yeuCau->diem_thuong} điểm vẫn được tạm giữ trong quỹ của người yêu cầu. Yêu cầu đã được mở lại để nhận tài liệu khác.",
        ];
    }
}
