<?php

namespace App\Services\Bounties\Actions;

use App\Models\DapUngYeuCauTaiLieu;
use App\Models\NguoiDung;
use App\Models\YeuCauTaiLieu;
use App\Services\Bounties\BountyRuleEvaluator;
use App\Services\Bounties\Repositories\WishlistBountyRepository;
use App\Services\Notifications\DTOs\NotificationPayload;
use App\Enums\NotificationCategory;
use App\Enums\NotificationType;
use App\Notifications\SystemNotification;
use Illuminate\Support\Facades\DB;

// Action xử lý khi người tạo từ chối bài đóng góp tài liệu (Phần 4.3, Trường hợp 2)
class RejectBountyFulfillmentAction
{
    public function __construct(
        protected WishlistBountyRepository $bountyRepository,
        protected BountyRuleEvaluator $ruleEvaluator
    ) {}

    public function execute(NguoiDung $user, int $maYeuCau, int $maDapUng, string $lyDo): array
    {
        $yeuCau = $this->bountyRepository->findRequestById($maYeuCau);
        $dapUng = $this->bountyRepository->findSubmission($maYeuCau, $maDapUng);

        $check = $this->ruleEvaluator->canRejectSubmission($user, $yeuCau, $dapUng);
        if (!$check['allowed']) {
            return [
                'success' => false,
                'message' => $check['message'],
            ];
        }

        DB::transaction(function () use ($dapUng, $lyDo) {
            $dapUng->update([
                'trang_thai'              => 'TU_CHOI',
                'ly_do_nguoi_tao_tu_choi' => $lyDo,
            ]);
        });

        // Bắn thông báo cho người đóng góp về lý do bị từ chối
        $nguoiDongGop = $dapUng->nguoiDongGop;
        if ($nguoiDongGop) {
            $tenTaiLieu = $dapUng->taiLieu->tieu_de ?? 'Tài liệu đóng góp';
            $payload = new NotificationPayload(
                type: NotificationType::BOUNTY_REJECTED,
                category: NotificationCategory::PERSONAL,
                title: 'Bài đóng góp tài liệu săn thưởng bị từ chối',
                message: "Người tạo yêu cầu #{$yeuCau->ma_yeu_cau} đã từ chối tài liệu \"{$tenTaiLieu}\". Lý do: {$lyDo}",
                targetType: 'YEU_CAU_TAI_LIEU',
                targetId: (string) $yeuCau->ma_yeu_cau,
                actionUrl: "/yeu-cau-tai-lieu/{$yeuCau->ma_yeu_cau}"
            );
            $nguoiDongGop->notify(new SystemNotification($payload));
        }

        return [
            'success' => true,
            'message' => 'Đã từ chối bài đóng góp thành công. Yêu cầu săn thưởng tiếp tục mở để nhận đóng góp khác.',
        ];
    }
}


