<?php

namespace App\Modules\Moderation\Application\Actions;

use App\Enums\TrangThaiTaiLieu;
use App\Models\DapUngYeuCauTaiLieu;
use App\Models\NguoiDung;
use App\Models\TaiLieu;
use App\Modules\Administration\Domain\Enums\HanhDongKiemToan;
use App\Modules\Administration\Domain\Enums\LoaiDoiTuongKiemToan;
use App\Modules\Administration\Domain\Services\AuditLogger;
use App\Modules\Moderation\Domain\Services\BountySubmissionModerationSynchronizer;
use App\Modules\Moderation\Infrastructure\Notifications\DocumentApprovedNotification;
use Illuminate\Support\Facades\DB;

// Action phê duyệt công khai tài liệu mới đăng đang ở trạng thái CHO_DUYET
class ApproveNewDocumentAction
{
    public function __construct(
        protected AuditLogger $auditLogger,
        protected BountySubmissionModerationSynchronizer $bountySynchronizer
    ) {}

    public function execute(int $documentId, NguoiDung $actor): array
    {
        // Kiểm tra quyền hạn: Chỉ Admin hoặc Kiểm duyệt viên
        if (!$actor->laQuanTriVien() && !$actor->laKiemDuyetVien()) {
            abort(403, 'Bạn không có quyền thực hiện kiểm duyệt tài liệu.');
        }

        $document = TaiLieu::with(['nguoiDang', 'monHoc'])->findOrFail($documentId);

        // Chỉ tài liệu ở trạng thái CHO_DUYET mới đi qua luồng phê duyệt ban đầu này
        if ($document->trang_thai !== 'CHO_DUYET' && $document->trang_thai !== TrangThaiTaiLieu::CHO_DUYET) {
            return [
                'success' => false,
                'message' => 'Tài liệu này không ở trạng thái chờ duyệt ban đầu.',
            ];
        }

        $bountyDisbursedCount = 0;

        DB::transaction(function () use ($document, $actor, &$bountyDisbursedCount) {
            // Cập nhật trạng thái thành DA_DUYET
            $document->update([
                'trang_thai'        => 'DA_DUYET',
                'ly_do_trang_thai' => "Đã duyệt bởi {$actor->ho_ten} lúc " . now()->format('d/m/Y H:i'),
            ]);

            // Ghi nhật ký kiểm toán hành vi phê duyệt tài liệu
            $this->auditLogger->log(
                actor: $actor,
                action: HanhDongKiemToan::DUYET_TAI_LIEU,
                targetType: LoaiDoiTuongKiemToan::TAI_LIEU,
                targetId: (string) $document->getKey(),
                description: "Phê duyệt tài liệu mới \"{$document->tieu_de}\" thuộc môn {$document->mon_hoc?->ten_mon_hoc}",
                oldData: ['trang_thai' => 'CHO_DUYET'],
                newData: ['trang_thai' => 'DA_DUYET']
            );

            // Gửi thông báo phê duyệt tới tác giả tải lên
            if ($document->nguoiDang) {
                $document->nguoiDang->notify(new DocumentApprovedNotification($document));
            }

            // Tự động giải ngân Bounty ngay cho người đóng góp nếu người yêu cầu (SV A) đã xác nhận tài liệu này
            $bountyDisbursedCount = $this->bountySynchronizer->syncOnDocumentApproved($document, $actor);

            // Nếu tài liệu nộp cho yêu cầu Bounty mà người yêu cầu chưa xác nhận, thông báo để họ vào nghiệm thu
            if ($bountyDisbursedCount === 0) {
                $bountySubmission = DapUngYeuCauTaiLieu::where('ma_tai_lieu', $document->ma_tai_lieu)
                    ->where('trang_thai', 'CHO_DUYET')
                    ->with('yeuCau.nguoiTao')
                    ->first();

                if ($bountySubmission?->yeuCau?->nguoiTao) {
                    $requester = $bountySubmission->yeuCau->nguoiTao;
                    $requester->notify(new DocumentApprovedNotification(
                        $document,
                        "Tài liệu đóng góp \"{$document->tieu_de}\" cho yêu cầu #{$bountySubmission->ma_yeu_cau} của bạn đã được duyệt. Bạn có thể vào kiểm tra và nghiệm thu."
                    ));
                }
            }
        });

        $message = "Đã phê duyệt công khai tài liệu \"{$document->tieu_de}\" thành công.";
        if ($bountyDisbursedCount > 0) {
            $message .= " Điểm thưởng Bounty đã được tự động giải ngân ngay cho người đóng góp.";
        }

        return [
            'success' => true,
            'message' => $message,
        ];
    }
}

