<?php

namespace App\Modules\Bounties\Application\Actions;

use App\Enums\TrangThaiYeuCauChinhSua;
use App\Models\LanNopTaiLieuThayThe;
use App\Models\NguoiDung;
use App\Models\YeuCauChinhSuaDongGop;
use App\Modules\Bounties\Domain\Services\BountyRuleEvaluator;
use App\Modules\Notifications\Application\DTOs\NotificationPayload;
use App\Modules\Notifications\Domain\Enums\NotificationCategory;
use App\Modules\Notifications\Domain\Enums\NotificationType;
use App\Modules\Notifications\Infrastructure\Notifications\SystemNotification;
use Illuminate\Support\Facades\DB;

// Action cho người tạo Bounty nghiệm thu hoàn tất chỉnh sửa tài liệu (Phần 6.4)
class ConfirmCorrectionCompletionAction
{
    public function __construct(
        protected BountyRuleEvaluator $ruleEvaluator
    ) {}

    public function execute(NguoiDung $user, int $maYeuCauChinhSua, int $maLanNopThayThe): array
    {
        $yccs = YeuCauChinhSuaDongGop::with(['yeuCau', 'nguoiDongGop', 'lanNopThayThes'])->findOrFail($maYeuCauChinhSua);
        $submission = LanNopTaiLieuThayThe::with('taiLieu')->findOrFail($maLanNopThayThe);

        $check = $this->ruleEvaluator->canConfirmCorrectionCompletion($user, $yccs, $submission);
        if (!$check['allowed']) {
            return [
                'success' => false,
                'message' => $check['message'],
            ];
        }

        DB::transaction(function () use ($yccs, $submission) {
            // Đánh dấu phiên bản được chấp nhận
            $yccs->lanNopThayThes()->update(['la_phien_ban_duoc_chap_nhan' => false]);
            $submission->update(['la_phien_ban_duoc_chap_nhan' => true]);

            // Cập nhật trạng thái yêu cầu chỉnh sửa sang HOAN_THANH
            $yccs->update([
                'trang_thai'         => TrangThaiYeuCauChinhSua::HOAN_THANH,
                'thoi_gian_hoan_tat' => now(),
            ]);

            // Cập nhật tài liệu hiển thị mặc định của Bounty sang phiên bản mới được chấp nhận
            $yccs->yeuCau()->update([
                'ma_tai_lieu_dap_ung' => $submission->ma_tai_lieu,
            ]);
        });

        // Bắn thông báo cho người đóng góp (B)
        $nguoiDongGop = $yccs->nguoiDongGop;
        if ($nguoiDongGop) {
            $payload = new NotificationPayload(
                type: NotificationType::CORRECTION_COMPLETED,
                category: NotificationCategory::PERSONAL,
                title: 'Yêu cầu chỉnh sửa đã được nghiệm thu hoàn thành',
                message: "Người tạo yêu cầu #{$yccs->ma_yeu_cau} đã nghiệm thu phiên bản tài liệu thay thế của bạn. Cảm ơn sự hỗ trợ nhiệt tình của bạn!",
                targetType: 'YEU_CAU_TAI_LIEU',
                targetId: (string) $yccs->ma_yeu_cau,
                actionUrl: "/yeu-cau-tai-lieu/{$yccs->ma_yeu_cau}"
            );
            $nguoiDongGop->notify(new SystemNotification($payload));
        }

        return [
            'success' => true,
            'message' => 'Đã nghiệm thu hoàn thành chỉnh sửa tài liệu thành công. Tài liệu mới đã được cập nhật làm bản hiển thị mặc định.',
        ];
    }
}
