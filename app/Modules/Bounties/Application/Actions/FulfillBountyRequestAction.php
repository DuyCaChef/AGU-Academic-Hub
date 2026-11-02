<?php

namespace App\Modules\Bounties\Application\Actions;

use App\Models\DapUngYeuCauTaiLieu;
use App\Models\NguoiDung;
use App\Modules\Bounties\Domain\Services\BountyRuleEvaluator;
use App\Modules\Bounties\Infrastructure\Persistence\WishlistBountyRepository;

// Action tiếp nhận tài liệu đóng góp phản hồi yêu cầu
class FulfillBountyRequestAction
{
    public function __construct(
        protected WishlistBountyRepository $bountyRepository,
        protected BountyRuleEvaluator $ruleEvaluator
    ) {}

    public function execute(NguoiDung $user, int $maYeuCau, array $validated): array
    {
        $yeuCau = $this->bountyRepository->findRequestById($maYeuCau);

        // Kiểm tra điều kiện có được nộp bài hay không
        $check = $this->ruleEvaluator->canFulfill($user, $yeuCau);
        if (!$check['allowed']) {
            return [
                'success' => false,
                'message' => $check['message'],
            ];
        }

        $documentId = (int) $validated['ma_tai_lieu'];

        // Kiểm tra xem đã gửi tài liệu này cho yêu cầu chưa
        if ($this->bountyRepository->submissionExists($yeuCau->ma_yeu_cau, $documentId)) {
            return [
                'success' => false,
                'message' => 'Tài liệu này đã được gửi phản hồi trước đó.',
            ];
        }

        $this->bountyRepository->createSubmission([
            'ma_yeu_cau'        => $yeuCau->ma_yeu_cau,
            'ma_tai_lieu'       => $documentId,
            'ma_nguoi_dong_gop' => $user->ma_nguoi_dung,
            'trang_thai'        => 'CHO_DUYET',
            'ghi_chu_dong_gop'  => $validated['ghi_chu_dong_gop'] ?? null,
        ]);

        return [
            'success' => true,
            'message' => 'Đã gửi tài liệu phản hồi. Chờ người yêu cầu duyệt và giải ngân điểm!',
        ];
    }
}

