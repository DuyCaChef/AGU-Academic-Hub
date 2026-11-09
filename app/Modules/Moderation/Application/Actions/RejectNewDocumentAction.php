<?php

namespace App\Modules\Moderation\Application\Actions;

use App\Enums\TrangThaiTaiLieu;
use App\Models\NguoiDung;
use App\Models\TaiLieu;
use App\Modules\Administration\Domain\Enums\HanhDongKiemToan;
use App\Modules\Administration\Domain\Enums\LoaiDoiTuongKiemToan;
use App\Modules\Administration\Domain\Services\AuditLogger;
use App\Modules\Moderation\Domain\Services\BountySubmissionModerationSynchronizer;
use App\Modules\Moderation\Infrastructure\Notifications\DocumentRejectedNotification;
use Illuminate\Support\Facades\DB;

// Action từ chối duyệt tài liệu mới đăng kèm lý do gửi cho tác giả
class RejectNewDocumentAction
{
    public function __construct(
        protected AuditLogger $auditLogger,
        protected BountySubmissionModerationSynchronizer $bountySynchronizer
    ) {}

    public function execute(int $documentId, string $reason, NguoiDung $actor): array
    {
        // Kiểm tra quyền hạn: Chỉ Admin hoặc Kiểm duyệt viên
        if (!$actor->laQuanTriVien() && !$actor->laKiemDuyetVien()) {
            abort(403, 'Bạn không có quyền thực hiện kiểm duyệt tài liệu.');
        }

        $document = TaiLieu::with(['nguoiDang', 'monHoc'])->findOrFail($documentId);

        // Chỉ tài liệu ở trạng thái CHO_DUYET mới đi qua luồng từ chối này
        if ($document->trang_thai !== 'CHO_DUYET' && $document->trang_thai !== TrangThaiTaiLieu::CHO_DUYET) {
            return [
                'success' => false,
                'message' => 'Tài liệu này không ở trạng thái chờ duyệt ban đầu.',
            ];
        }

        DB::transaction(function () use ($document, $reason, $actor) {
            // Cập nhật trạng thái thành TU_CHOI
            $document->update([
                'trang_thai'        => 'TU_CHOI',
                'ly_do_trang_thai' => "Bị từ chối bởi {$actor->ho_ten}: {$reason}",
            ]);

            // Nếu tài liệu nộp cho Bounty, đồng bộ từ chối bài nộp tương ứng
            $this->bountySynchronizer->syncOnDocumentDeleted(
                document: $document,
                actor: $actor,
                reason: "Tài liệu bị Ban kiểm duyệt từ chối: {$reason}"
            );

            // Ghi nhật ký kiểm toán hành vi từ chối tài liệu
            $this->auditLogger->log(
                actor: $actor,
                action: HanhDongKiemToan::TU_CHOI_TAI_LIEU,
                targetType: LoaiDoiTuongKiemToan::TAI_LIEU,
                targetId: (string) $document->getKey(),
                description: "Từ chối duyệt tài liệu mới \"{$document->tieu_de}\". Lý do: {$reason}",
                oldData: ['trang_thai' => 'CHO_DUYET'],
                newData: ['trang_thai' => 'TU_CHOI', 'ly_do' => $reason]
            );

            // Gửi thông báo từ chối kèm lý do cụ thể tới tác giả tải lên
            if ($document->nguoiDang) {
                $document->nguoiDang->notify(new DocumentRejectedNotification($document, $reason));
            }
        });

        return [
            'success' => true,
            'message' => "Đã từ chối duyệt tài liệu \"{$document->tieu_de}\". Lý do đã được gửi tới tác giả.",
        ];
    }
}

