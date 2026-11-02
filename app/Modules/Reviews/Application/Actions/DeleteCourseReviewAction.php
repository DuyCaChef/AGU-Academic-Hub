<?php

namespace App\Modules\Reviews\Application\Actions;

use App\Models\NguoiDung;
use App\Modules\Reviews\Domain\Services\CourseReviewRuleEvaluator;
use App\Modules\Reviews\Infrastructure\Persistence\CourseReviewRepository;

// Action xóa mềm bài đánh giá môn học
class DeleteCourseReviewAction
{
    public function __construct(
        protected CourseReviewRepository $reviewRepository,
        protected CourseReviewRuleEvaluator $ruleEvaluator
    ) {}

    public function execute(NguoiDung $user, int $maDanhGia): bool
    {
        $danhGia = $this->reviewRepository->findReviewById($maDanhGia);

        // Quyền xóa: Tác giả bài viết, Quản trị viên hoặc Kiểm duyệt viên
        if (!$this->ruleEvaluator->canDelete($user, $danhGia)) {
            abort(403, 'Bạn không có thẩm quyền xóa bài đánh giá này.');
        }

        return $this->reviewRepository->deleteReview($danhGia);
    }
}

