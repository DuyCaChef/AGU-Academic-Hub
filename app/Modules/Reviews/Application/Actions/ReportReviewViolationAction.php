<?php

namespace App\Modules\Reviews\Application\Actions;

use App\Models\BaoCaoDanhGia;
use App\Models\NguoiDung;
use App\Modules\Reviews\Infrastructure\Persistence\CourseReviewRepository;

// Action tiếp nhận và ghi nhận báo cáo vi phạm tiêu chuẩn cộng đồng cho bài đánh giá
class ReportReviewViolationAction
{
    public function __construct(
        protected CourseReviewRepository $reviewRepository
    ) {}

    public function execute(NguoiDung $user, int $maDanhGia, string $lyDo, ?string $chiTiet = null): BaoCaoDanhGia
    {
        $danhGia = $this->reviewRepository->findReviewById($maDanhGia);
        $reporterId = $user->ma_nguoi_dung ?? $user->getKey();

        $validReasons = ['NGON_TU_KICH_DONG', 'BOI_NHO', 'QUAY_ROI', 'SPAM', 'KHAC'];
        $cleanReason = in_array($lyDo, $validReasons, true) ? $lyDo : 'KHAC';

        return $this->reviewRepository->reportReview(
            $danhGia->ma_danh_gia,
            $reporterId,
            $cleanReason,
            $chiTiet
        );
    }
}

