<?php

namespace App\Modules\Bounties\Application\Actions;

use App\Enums\TrangThaiYeuCauTaiLieu;
use App\Models\NguoiDung;
use App\Modules\Bounties\Domain\Services\BountyRuleEvaluator;
use App\Modules\Bounties\Domain\Services\ReputationEvaluator;
use App\Modules\Bounties\Infrastructure\Persistence\WishlistBountyRepository;
use Illuminate\Support\Facades\DB;

// Action hủy yêu cầu tìm tài liệu và hoàn trả điểm thưởng đã đóng băng vào ví
class CancelBountyRequestAction
{
    public function __construct(
        protected WishlistBountyRepository $bountyRepository,
        protected BountyRuleEvaluator $ruleEvaluator,
        protected ReputationEvaluator $reputationEvaluator
    ) {}

    public function execute(NguoiDung $user, int $maYeuCau): array
    {
        $yeuCau = $this->bountyRepository->findRequestById($maYeuCau);

        // Quyền hủy: Chính chủ người tạo hoặc Quản trị viên
        if (!$this->ruleEvaluator->canCancel($user, $yeuCau)) {
            abort(403, 'Bạn không có quyền hủy yêu cầu này.');
        }

        if ($yeuCau->trang_thai !== TrangThaiYeuCauTaiLieu::MO) {
            return [
                'success' => false,
                'message' => 'Chỉ có thể hủy yêu cầu đang ở trạng thái Mở (MO).',
            ];
        }

        DB::transaction(function () use ($yeuCau) {
            // 1. Hoàn trả điểm qua ReputationEvaluator
            $this->reputationEvaluator->hoanDiemBounty($yeuCau);

            // 2. Chuyển trạng thái sang DA_HUY và Soft Delete
            $this->bountyRepository->cancelRequest($yeuCau);
        });

        return [
            'success' => true,
            'message' => "Đã hủy yêu cầu và hoàn lại {$yeuCau->diem_thuong} điểm vào ví của bạn.",
        ];
    }
}

