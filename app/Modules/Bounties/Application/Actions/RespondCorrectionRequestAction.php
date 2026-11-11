<?php

namespace App\Modules\Bounties\Application\Actions;

use App\Enums\TrangThaiYeuCauChinhSua;
use App\Models\NguoiDung;
use App\Models\YeuCauChinhSuaDongGop;
use App\Modules\Bounties\Domain\Services\BountyRuleEvaluator;
use App\Modules\Notifications\Application\DTOs\NotificationPayload;
use App\Modules\Notifications\Domain\Enums\NotificationCategory;
use App\Modules\Notifications\Domain\Enums\NotificationType;
use App\Modules\Notifications\Infrastructure\Notifications\SystemNotification;
use Illuminate\Support\Facades\DB;

// Action cho người đóng góp phản hồi yêu cầu chỉnh sửa (Chấp nhận hoặc Từ chối) (Phần 6.3)
class RespondCorrectionRequestAction
{
    public function __construct(
        protected BountyRuleEvaluator $ruleEvaluator
    ) {}

    public function execute(NguoiDung $user, int $maYeuCauChinhSua, string $hanhDong, ?string $lyDoTuChoi = null): array
    {
        $yccs = YeuCauChinhSuaDongGop::with(['yeuCau', 'nguoiYeuCau'])->findOrFail($maYeuCauChinhSua);

        $check = $this->ruleEvaluator->canRespondToCorrection($user, $yccs);
        if (!$check['allowed']) {
            return [
                'success' => false,
                'message' => $check['message'],
            ];
        }

        $isAccept = (strtoupper($hanhDong) === 'CHAP_NHAN');

        DB::transaction(function () use ($yccs, $isAccept, $lyDoTuChoi) {
            if ($isAccept) {
                $yccs->update([
                    'trang_thai'         => TrangThaiYeuCauChinhSua::DANG_XU_LY,
                    'thoi_gian_phan_hoi' => now(),
                ]);
            } else {
                $yccs->update([
                    'trang_thai'         => TrangThaiYeuCauChinhSua::TU_CHOI,
                    'ly_do_tu_choi'      => $lyDoTuChoi ?? 'Người đóng góp từ chối hỗ trợ chỉnh sửa thêm.',
                    'thoi_gian_phan_hoi' => now(),
                    'thoi_gian_hoan_tat' => now(),
                ]);
            }
        });

        // Bắn thông báo cho người tạo yêu cầu (A)
        $nguoiYeuCau = $yccs->nguoiYeuCau;
        if ($nguoiYeuCau) {
            $msg = $isAccept
                ? "Người đóng góp đã chấp nhận yêu cầu chỉnh sửa và đang chuẩn bị phiên bản thay thế."
                : "Người đóng góp đã từ chối yêu cầu chỉnh sửa. Lý do: " . ($lyDoTuChoi ?? 'Không nêu lý do.');

            $payload = new NotificationPayload(
                type: $isAccept ? NotificationType::CORRECTION_ACCEPTED : NotificationType::CORRECTION_REJECTED,
                category: NotificationCategory::PERSONAL,
                title: $isAccept ? 'Yêu cầu chỉnh sửa được chấp nhận' : 'Yêu cầu chỉnh sửa bị từ chối',
                message: $msg,
                targetType: 'YEU_CAU_TAI_LIEU',
                targetId: (string) $yccs->ma_yeu_cau,
                actionUrl: "/yeu-cau-tai-lieu/{$yccs->ma_yeu_cau}"
            );
            $nguoiYeuCau->notify(new SystemNotification($payload));
        }

        return [
            'success' => true,
            'message' => $isAccept ? 'Đã chấp nhận yêu cầu chỉnh sửa. Bạn có thể tải lên tài liệu thay thế ngay bây giờ.' : 'Đã từ chối yêu cầu chỉnh sửa.',
        ];
    }
}
