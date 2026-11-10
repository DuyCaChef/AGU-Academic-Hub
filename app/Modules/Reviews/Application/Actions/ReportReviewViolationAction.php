<?php

namespace App\Modules\Reviews\Application\Actions;

use App\Models\BaoCaoDanhGia;
use App\Models\NguoiDung;
use App\Modules\Moderation\Domain\Services\ReviewReportRecalculator;
use App\Modules\Notifications\Domain\Events\ReviewReportedEvent;
use App\Modules\Reviews\Infrastructure\Persistence\CourseReviewRepository;

// Action tiếp nhận và ghi nhận báo cáo vi phạm tiêu chuẩn cộng đồng cho bài đánh giá
class ReportReviewViolationAction
{
    public function __construct(
        protected CourseReviewRepository $reviewRepository,
        protected ReviewReportRecalculator $recalculator
    ) {}

    public function execute(
        NguoiDung $user,
        int $maDanhGia,
        string $lyDo,
        ?string $chiTiet = null,
        ?string $loaiDoiTuong = null,
        ?string $tenGiangVien = null
    ): BaoCaoDanhGia {
        $danhGia = $this->reviewRepository->findReviewById($maDanhGia);

        $report = $this->recalculator->createReport(
            $danhGia,
            $user,
            [
                'ly_do'          => $lyDo,
                'chi_tiet'       => $chiTiet,
                'loai_doi_tuong' => $loaiDoiTuong,
                'ten_giang_vien' => $tenGiangVien,
            ]
        );

        ReviewReportedEvent::dispatch($report, $danhGia, $user);

        return $report;
    }
}
