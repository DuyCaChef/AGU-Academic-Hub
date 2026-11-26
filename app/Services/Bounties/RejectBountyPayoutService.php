<?php

namespace App\Services\Bounties;

use App\Enums\HanhDongKiemToan;
use App\Enums\LoaiDoiTuongKiemToan;
use App\Enums\TrangThaiYeuCauTaiLieu;
use App\Models\NguoiDung;
use App\Notifications\BountyPayoutRejectedNotification;
use App\Services\Admin\AuditLogger;
use App\Services\Bounties\Repositories\WishlistBountyRepository;
use App\Services\Wallet\ReputationEvaluator;
use Illuminate\Support\Facades\DB;

class RejectBountyPayoutService
{
    public function __construct(
        protected WishlistBountyRepository $bountyRepository,
        protected ReputationEvaluator $reputationEvaluator,
        protected AuditLogger $auditLogger
    ) {}

    public function execute(NguoiDung $actor, int $maYeuCau, string $lyDo): array
    {
        if (!$actor->laQuanTriVien() && !$actor->laKiemDuyetVien()) {
            abort(403, 'Bạn không có quyền từ chối giải ngân điểm Bounty.');
        }

        $yeuCau = $this->bountyRepository->getPendingPayoutDetail($maYeuCau);

        if ($yeuCau->trang_thai !== TrangThaiYeuCauTaiLieu::CHO_GIAI_NGAN) {
            return ['success' => false, 'message' => 'Yêu cầu này không ở trạng thái chờ duyệt giải ngân.'];
        }

        $dapUng = $yeuCau->dapUngDuocChon;
        if (!$dapUng) {
            return ['success' => false, 'message' => 'Không tìm thấy bài đóng góp được chọn để từ chối.'];
        }

        $nguoiDongGop = $dapUng->nguoiDongGop;
        $nguoiYeuCau = $yeuCau->nguoiTao;

        DB::transaction(function () use ($yeuCau, $dapUng, $actor, $lyDo): void {
            if ($dapUng->taiLieu && $dapUng->taiLieu->trang_thai === 'CHO_DUYET') {
                $dapUng->taiLieu->update([
                    'trang_thai' => 'TU_CHOI',
                    'ly_do_trang_thai' => "Bị Ban kiểm duyệt từ chối: {$lyDo}",
                ]);
            }

            $this->reputationEvaluator->hoanDiemBounty(
                $yeuCau,
                "tu_choi_giai_ngan_yc_{$yeuCau->ma_yeu_cau}"
            );
            $this->bountyRepository->rejectPayout($yeuCau, $dapUng, $lyDo, $actor->ma_nguoi_dung);

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

        $nguoiYeuCau->notify(new BountyPayoutRejectedNotification(
            maYeuCau: $yeuCau->ma_yeu_cau,
            diemThuong: $yeuCau->diem_thuong,
            lyDo: $lyDo,
            recipient: 'nguoi_yeu_cau'
        ));
        $nguoiDongGop->notify(new BountyPayoutRejectedNotification(
            maYeuCau: $yeuCau->ma_yeu_cau,
            diemThuong: $yeuCau->diem_thuong,
            lyDo: $lyDo,
            recipient: 'nguoi_dong_gop'
        ));

        return [
            'success' => true,
            'message' => "Đã từ chối bài đóng góp. Điểm thưởng {$yeuCau->diem_thuong} điểm đã được hoàn lại cho người yêu cầu. Yêu cầu đã được mở lại để nhận tài liệu khác.",
        ];
    }
}


