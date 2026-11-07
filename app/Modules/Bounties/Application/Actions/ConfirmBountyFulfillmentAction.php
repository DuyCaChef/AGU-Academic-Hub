<?php

namespace App\Modules\Bounties\Application\Actions;

use App\Enums\TrangThaiYeuCauTaiLieu;
use App\Models\NguoiDung;
use App\Modules\Bounties\Domain\Services\BountyRuleEvaluator;
use App\Modules\Bounties\Domain\Services\ReputationEvaluator;
use App\Modules\Bounties\Infrastructure\Persistence\WishlistBountyRepository;
use Illuminate\Support\Facades\DB;

// Action xác nhận giải ngân điểm thưởng cho người đóng góp tài liệu
class ConfirmBountyFulfillmentAction
{
    public function __construct(
        protected WishlistBountyRepository $bountyRepository,
        protected BountyRuleEvaluator $ruleEvaluator,
        protected ReputationEvaluator $reputationEvaluator
    ) {}

    public function execute(NguoiDung $user, int $maYeuCau, int $maDapUng): array
    {
        $yeuCau = $this->bountyRepository->findRequestById($maYeuCau);

        // Quyền xác nhận: Chỉ Người tạo hoặc Quản trị viên/Kiểm duyệt viên
        if (!$this->ruleEvaluator->canConfirm($user, $yeuCau)) {
            abort(403, 'Bạn không có quyền xác nhận giải ngân cho yêu cầu này.');
        }

        if ($yeuCau->trang_thai !== TrangThaiYeuCauTaiLieu::MO) {
            return [
                'success' => false,
                'message' => 'Yêu cầu này đã được hoàn thành hoặc đã bị hủy.',
            ];
        }

        $dapUng = $this->bountyRepository->findSubmission($yeuCau->ma_yeu_cau, $maDapUng);

        // Bảo vệ điểm thưởng: Tài liệu đóng góp bắt buộc phải được Ban kiểm duyệt phê duyệt trước khi giải ngân
        if ($dapUng->taiLieu && $dapUng->taiLieu->trang_thai !== 'DA_DUYET' && $dapUng->taiLieu->trang_thai !== \App\Enums\TrangThaiTaiLieu::DA_DUYET) {
            return [
                'success' => false,
                'message' => 'Tài liệu đóng góp này chưa được Ban kiểm duyệt phê duyệt, chưa thể xác nhận giải ngân điểm.',
            ];
        }

        DB::transaction(function () use ($yeuCau, $dapUng, $user) {
            // 1. Giải ngân điểm qua ReputationEvaluator
            $this->reputationEvaluator->giaiNganDiemBounty($yeuCau, $dapUng->nguoiDongGop);

            // 2. Cập nhật trạng thái các bản đáp ứng và hoàn thành yêu cầu
            $this->bountyRepository->updateConfirmedFulfillment(
                $yeuCau,
                $dapUng,
                $user->ma_nguoi_dung
            );
        });

        $contributorName = $dapUng->nguoiDongGop->ho_ten ?? 'người đóng góp';

        return [
            'success' => true,
            'message' => "Xác nhận thành công! Đã giải ngân {$yeuCau->diem_thuong} điểm cho {$contributorName}.",
        ];
    }
}

