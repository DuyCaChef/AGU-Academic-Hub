<?php

namespace App\Modules\Moderation\Domain\Services;

use App\Enums\TrangThaiYeuCauTaiLieu;
use App\Models\DapUngYeuCauTaiLieu;
use App\Models\NguoiDung;
use App\Models\TaiLieu;
use App\Modules\Administration\Domain\Enums\HanhDongKiemToan;
use App\Modules\Administration\Domain\Enums\LoaiDoiTuongKiemToan;
use App\Modules\Administration\Domain\Services\AuditLogger;
use App\Modules\Moderation\Infrastructure\Notifications\BountySubmissionCancelledNotification;

class BountySubmissionModerationSynchronizer
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    // Đồng bộ từ chối các bài nộp Bounty liên kết khi tài liệu bị Ban kiểm duyệt xóa hoặc từ chối
    public function syncOnDocumentDeleted(
        TaiLieu $document,
        NguoiDung $actor,
        string $reason = 'Tài liệu đính kèm đã bị Ban kiểm duyệt gỡ bỏ do vi phạm quy định.'
    ): int {
        // Tìm tất cả bài nộp Bounty đang liên kết và chưa bị từ chối trước đó
        $submissions = DapUngYeuCauTaiLieu::with(['yeuCau.nguoiTao', 'nguoiDongGop'])
            ->where('ma_tai_lieu', $document->getKey())
            ->whereIn('trang_thai', ['CHO_DUYET', 'NGUOI_YEU_CAU_XAC_NHAN'])
            ->get();

        if ($submissions->isEmpty()) {
            return 0;
        }

        $now = now();
        $count = 0;

        foreach ($submissions as $submission) {
            $oldStatus = $submission->trang_thai;

            // Cập nhật trạng thái bài nộp sang TU_CHOI
            $submission->update([
                'trang_thai'              => 'TU_CHOI',
                'ly_do_tu_choi_giai_ngan' => $reason,
                'nguoi_duyet'             => $actor->getKey(),
                'thoi_gian_duyet'         => $now,
            ]);

            $yeuCau = $submission->yeuCau;

            // Nếu yêu cầu đang chọn bài nộp này hoặc đang ở trạng thái chờ giải ngân, mở lại yêu cầu sang MO
            if ($yeuCau && ($yeuCau->ma_dap_ung_duoc_chon === $submission->ma_dap_ung || $yeuCau->trang_thai === TrangThaiYeuCauTaiLieu::CHO_GIAI_NGAN)) {
                $yeuCau->update([
                    'trang_thai'                       => TrangThaiYeuCauTaiLieu::MO,
                    'ma_dap_ung_duoc_chon'             => null,
                    'thoi_gian_nguoi_yeu_cau_xac_nhan' => null,
                ]);
            }

            // Ghi nhật ký kiểm toán cho bài nộp bị từ chối
            $this->auditLogger->log(
                actor: $actor,
                action: HanhDongKiemToan::TU_CHOI_TAI_LIEU,
                targetType: LoaiDoiTuongKiemToan::YEU_CAU_TAI_LIEU,
                targetId: (string) $submission->ma_yeu_cau,
                description: "Tự động từ chối bài nộp Bounty #{$submission->ma_dap_ung} cho yêu cầu #{$submission->ma_yeu_cau} do tài liệu \"{$document->tieu_de}\" bị xử lý vi phạm. Lý do: {$reason}",
                oldData: ['trang_thai' => $oldStatus],
                newData: ['trang_thai' => 'TU_CHOI', 'ly_do_tu_choi_giai_ngan' => $reason]
            );

            // Gửi thông báo đến người tạo yêu cầu (SV A) nếu có
            if ($yeuCau?->nguoiTao && !$yeuCau->nguoiTao->is($actor)) {
                $yeuCau->nguoiTao->notify(new BountySubmissionCancelledNotification(
                    maYeuCau: $yeuCau->ma_yeu_cau,
                    documentTitle: $document->tieu_de,
                    reason: $reason
                ));
            }

            $count++;
        }

        return $count;
    }
}

