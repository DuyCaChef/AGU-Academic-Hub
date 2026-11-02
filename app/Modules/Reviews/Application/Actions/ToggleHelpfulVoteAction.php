<?php

namespace App\Modules\Reviews\Application\Actions;

use App\Models\NguoiDung;
use App\Modules\Reviews\Domain\Services\CourseReviewRuleEvaluator;
use App\Modules\Reviews\Infrastructure\Persistence\CourseReviewRepository;

// Action chuyển đổi trạng thái bình chọn đánh giá hữu ích
class ToggleHelpfulVoteAction
{
    public function __construct(
        protected CourseReviewRepository $reviewRepository,
        protected CourseReviewRuleEvaluator $ruleEvaluator
    ) {}

    public function execute(NguoiDung $user, int $maDanhGia): array
    {
        $danhGia = $this->reviewRepository->findReviewById($maDanhGia);

        // Không cho phép tự vote cho bài của chính mình
        if (!$this->ruleEvaluator->canVoteHelpful($user, $danhGia)) {
            return [
                'status'  => 'warning',
                'message' => 'Bạn không thể tự bình chọn đánh giá của chính mình.',
            ];
        }

        $userId = $user->ma_nguoi_dung ?? $user->getKey();
        $isVoted = $this->reviewRepository->toggleHelpfulVote($danhGia->ma_danh_gia, $userId);

        if ($isVoted) {
            return [
                'status'  => 'success',
                'message' => 'Đã ghi nhận đánh giá hữu ích.',
            ];
        }

        return [
            'status'  => 'info',
            'message' => 'Đã bỏ bình chọn hữu ích.',
        ];
    }
}

