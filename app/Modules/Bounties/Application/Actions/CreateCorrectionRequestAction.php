<?php

namespace App\Modules\Bounties\Application\Actions;

use App\Enums\TrangThaiYeuCauChinhSua;
use App\Models\NguoiDung;
use App\Models\YeuCauChinhSuaDongGop;
use App\Models\YeuCauTaiLieu;
use App\Modules\Bounties\Domain\Services\BountyRuleEvaluator;
use App\Modules\Bounties\Infrastructure\Persistence\WishlistBountyRepository;
use App\Modules\Notifications\Application\DTOs\NotificationPayload;
use App\Modules\Notifications\Domain\Enums\NotificationCategory;
use App\Modules\Notifications\Domain\Enums\NotificationType;
use App\Modules\Notifications\Infrastructure\Notifications\SystemNotification;
use Illuminate\Support\Facades\DB;

// Action tạo yêu cầu chỉnh sửa sau khi Bounty hoàn thành (Phần 6)
class CreateCorrectionRequestAction
{
    public function __construct(
        protected WishlistBountyRepository $bountyRepository,
        protected BountyRuleEvaluator $ruleEvaluator
    ) {}

    public function execute(NguoiDung $user, int $maYeuCau, string $lyDoChinhSua): array
    {
        $yeuCau = $this->bountyRepository->findRequestById($maYeuCau);

        $check = $this->ruleEvaluator->canRequestCorrection($user, $yeuCau);
        if (!$check['allowed']) {
            return [
                'success' => false,
                'message' => $check['message'],
            ];
        }

        $dapUng = $yeuCau->dapUngDuocChon;
        if (!$dapUng) {
            // Trường hợp nếu ma_dap_ung_duoc_chon null nhưng ma_tai_lieu_dap_ung có giá trị
            $dapUng = $yeuCau->dapUngs()->where('trang_thai', 'CHAP_NHAN')->first();
        }

        if (!$dapUng) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy bài đóng góp đã được chấp nhận của yêu cầu này.',
            ];
        }

        $correction = DB::transaction(function () use ($user, $yeuCau, $dapUng, $lyDoChinhSua) {
            return YeuCauChinhSuaDongGop::create([
                'ma_yeu_cau'        => $yeuCau->ma_yeu_cau,
                'ma_dap_ung'        => $dapUng->ma_dap_ung,
                'ma_nguoi_yeu_cau'  => $user->ma_nguoi_dung,
                'ma_nguoi_dong_gop' => $dapUng->ma_nguoi_dong_gop,
                'ly_do_chinh_sua'   => trim($lyDoChinhSua),
                'trang_thai'        => TrangThaiYeuCauChinhSua::CHO_XU_LY,
            ]);
        });

        // Bắn thông báo cho người đóng góp (B)
        $nguoiDongGop = $dapUng->nguoiDongGop;
        if ($nguoiDongGop) {
            $payload = new NotificationPayload(
                type: NotificationType::CORRECTION_REQUESTED,
                category: NotificationCategory::PERSONAL,
                title: 'Yêu cầu chỉnh sửa tài liệu sau hoàn thành',
                message: "Người tạo yêu cầu #{$yeuCau->ma_yeu_cau} đã gửi yêu cầu chỉnh sửa/bổ sung tài liệu. Lý do: {$lyDoChinhSua}",
                targetType: 'YEU_CAU_TAI_LIEU',
                targetId: (string) $yeuCau->ma_yeu_cau,
                actionUrl: "/yeu-cau-tai-lieu/{$yeuCau->ma_yeu_cau}"
            );
            $nguoiDongGop->notify(new SystemNotification($payload));
        }

        return [
            'success'    => true,
            'message'    => 'Đã gửi yêu cầu chỉnh sửa tới người đóng góp thành công.',
            'correction' => $correction,
        ];
    }
}
