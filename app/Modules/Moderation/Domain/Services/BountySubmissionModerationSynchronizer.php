<?php

namespace App\Modules\Moderation\Domain\Services;

use App\Enums\TrangThaiYeuCauTaiLieu;
use App\Models\DapUngYeuCauTaiLieu;
use App\Models\NguoiDung;
use App\Models\TaiLieu;
use App\Modules\Administration\Domain\Enums\HanhDongKiemToan;
use App\Modules\Administration\Domain\Enums\LoaiDoiTuongKiemToan;
use App\Modules\Administration\Domain\Services\AuditLogger;
use App\Modules\Bounties\Domain\Services\ReputationEvaluator;
use App\Modules\Moderation\Infrastructure\Notifications\BountyPayoutClawbackRefundNotification;
use App\Modules\Moderation\Infrastructure\Notifications\BountyPayoutClawedBackNotification;
use App\Modules\Moderation\Infrastructure\Notifications\BountySubmissionCancelledNotification;

class BountySubmissionModerationSynchronizer
{
    public function __construct(
        protected AuditLogger $auditLogger,
        protected ReputationEvaluator $reputationEvaluator
    ) {}

    // Đồng bộ từ chối hoặc thu hồi (Clawback) bài nộp Bounty liên kết khi tài liệu bị Ban kiểm duyệt gỡ bỏ do vi phạm
    public function syncOnDocumentDeleted(
        TaiLieu $document,
        NguoiDung $actor,
        string $reason = 'Tài liệu đính kèm đã bị Ban kiểm duyệt gỡ bỏ do vi phạm quy định.'
    ): int {
        // Tìm tất cả bài nộp Bounty đang liên kết và chưa bị từ chối trước đó (bao gồm cả bài nộp ĐÃ DUYỆT GIẢI NGÂN - CHAP_NHAN)
        $submissions = DapUngYeuCauTaiLieu::with(['yeuCau.nguoiTao', 'nguoiDongGop'])
            ->where('ma_tai_lieu', $document->getKey())
            ->whereIn('trang_thai', ['CHO_DUYET', 'NGUOI_YEU_CAU_XAC_NHAN', 'CHAP_NHAN'])
            ->get();

        if ($submissions->isEmpty()) {
            return 0;
        }

        $now = now();
        $count = 0;

        foreach ($submissions as $submission) {
            $oldStatus = $submission->trang_thai;
            $yeuCau = $submission->yeuCau;

            if ($oldStatus === 'CHAP_NHAN') {
                // QUY TRÌNH THU HỒI ĐIỂM (CLAWBACK) CHO TÀI LIỆU ĐÃ GIẢI NGÂN
                $nguoiDongGop = $submission->nguoiDongGop;
                $nguoiTao = $yeuCau?->nguoiTao;

                $clawbackResult = ['diem_thuong' => 0, 'diem_thu_hoi' => 0, 'diem_hoan_tra' => 0];

                if ($yeuCau && $nguoiDongGop && $nguoiTao) {
                    $clawbackResult = $this->reputationEvaluator->thuHoiDiemBounty(
                        yeuCau: $yeuCau,
                        dapUng: $submission,
                        nguoiDongGop: $nguoiDongGop,
                        nguoiTao: $nguoiTao,
                        lyDo: $reason
                    );
                }

                // Cập nhật trạng thái bài nộp sang TU_CHOI kèm lý do thu hồi
                $submission->update([
                    'trang_thai'              => 'TU_CHOI',
                    'ly_do_tu_choi_giai_ngan' => "Thu hồi giải ngân do tài liệu bị xóa vì vi phạm: {$reason}",
                    'nguoi_duyet'             => $actor->getKey(),
                    'thoi_gian_duyet'         => $now,
                ]);

                // Hủy yêu cầu Bounty vì người yêu cầu (SV A) đã nhận lại điểm thưởng đền bù vào ví khả dụng
                if ($yeuCau) {
                    $yeuCau->update([
                        'trang_thai'                       => TrangThaiYeuCauTaiLieu::DA_HUY,
                        'ma_tai_lieu_dap_ung'             => null,
                        'ma_dap_ung_duoc_chon'             => null,
                        'thoi_gian_nguoi_yeu_cau_xac_nhan' => null,
                    ]);
                }

                // Ghi nhật ký kiểm toán hành vi thu hồi giải ngân Bounty
                $this->auditLogger->log(
                    actor: $actor,
                    action: HanhDongKiemToan::THU_HOI_GIAI_NGAN_BOUNTY,
                    targetType: LoaiDoiTuongKiemToan::YEU_CAU_TAI_LIEU,
                    targetId: (string) ($yeuCau?->ma_yeu_cau ?? $submission->ma_yeu_cau),
                    description: "Thu hồi giải ngân Bounty: Đã thu hồi {$clawbackResult['diem_thu_hoi']} điểm từ {$nguoiDongGop?->ho_ten} và hoàn trả đền bù {$clawbackResult['diem_hoan_tra']} điểm cho {$nguoiTao?->ho_ten} (Yêu cầu #{$submission->ma_yeu_cau}) do tài liệu \"{$document->tieu_de}\" bị gỡ bỏ vì vi phạm. Lý do: {$reason}",
                    oldData: ['trang_thai' => 'CHAP_NHAN'],
                    newData: [
                        'trang_thai'     => 'TU_CHOI',
                        'diem_thu_hoi'  => $clawbackResult['diem_thu_hoi'],
                        'diem_hoan_tra' => $clawbackResult['diem_hoan_tra'],
                    ]
                );

                // Gửi thông báo hoàn tiền đền bù cho SV A (người tạo yêu cầu)
                if ($nguoiTao && !$nguoiTao->is($actor)) {
                    $nguoiTao->notify(new BountyPayoutClawbackRefundNotification(
                        maYeuCau: $submission->ma_yeu_cau,
                        documentTitle: $document->tieu_de,
                        diemHoanTra: $clawbackResult['diem_hoan_tra'],
                        reason: $reason
                    ));
                }

                // Gửi thông báo thu hồi điểm cho SV B (người đóng góp vi phạm)
                if ($nguoiDongGop && !$nguoiDongGop->is($actor)) {
                    $nguoiDongGop->notify(new BountyPayoutClawedBackNotification(
                        maYeuCau: $submission->ma_yeu_cau,
                        documentTitle: $document->tieu_de,
                        diemThuHoi: $clawbackResult['diem_thu_hoi'],
                        reason: $reason
                    ));
                }
            } else {
                // QUY TRÌNH TỪ CHỐI BÀI NỘP CHƯA GIẢI NGÂN (CHO_DUYET hoặc NGUOI_YEU_CAU_XAC_NHAN)
                $submission->update([
                    'trang_thai'              => 'TU_CHOI',
                    'ly_do_tu_choi_giai_ngan' => $reason,
                    'nguoi_duyet'             => $actor->getKey(),
                    'thoi_gian_duyet'         => $now,
                ]);

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
            }

            $count++;
        }

        return $count;
    }
}
