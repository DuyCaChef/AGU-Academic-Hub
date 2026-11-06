<?php

namespace App\Modules\Reviews\Application\Actions;

use App\Enums\TrangThaiDanhGiaMonHoc;
use App\Models\NguoiDung;
use App\Modules\Reviews\Domain\Services\CourseReviewRuleEvaluator;
use App\Modules\Reviews\Infrastructure\Persistence\CourseReviewRepository;

// Action xử lý bình chọn cộng đồng (Upvote +1 / Downvote -1 / Toggle Helpful)
class ToggleHelpfulVoteAction
{
    public function __construct(
        protected CourseReviewRepository $reviewRepository,
        protected CourseReviewRuleEvaluator $ruleEvaluator
    ) {}

    // Phương thức thực thi mặc định (tương thích ngược với Helpful vote 1 chiều)
    public function execute(NguoiDung $user, int $maDanhGia): array
    {
        return $this->vote($user, $maDanhGia, 1);
    }

    // Phương thức bình chọn cộng đồng hai chiều (1 = Upvote, -1 = Downvote)
    public function vote(NguoiDung $user, int $maDanhGia, int $voteType = 1): array
    {
        $danhGia = $this->reviewRepository->findReviewById($maDanhGia);

        // Kiểm tra bài đánh giá có hợp lệ để bình chọn không (phải còn tồn tại và đã duyệt)
        if ($danhGia->trashed() || ($danhGia->trang_thai !== TrangThaiDanhGiaMonHoc::DA_DUYET && $danhGia->trang_thai !== 'DA_DUYET')) {
            return [
                'status'  => 'warning',
                'message' => 'Bài đánh giá này hiện không khả dụng để bình chọn.',
            ];
        }

        // Không cho phép tự vote cho bài của chính mình
        if (!$this->ruleEvaluator->canVoteHelpful($user, $danhGia)) {
            return [
                'status'  => 'warning',
                'message' => 'Bạn không thể tự bình chọn đánh giá của chính mình.',
            ];
        }

        $userId = $user->ma_nguoi_dung ?? $user->getKey();
        return $this->reviewRepository->castCommunityVote($danhGia->ma_danh_gia, $userId, $voteType);
    }
}
