<?php

namespace App\Services\Moderation\Queries;

use App\Services\Moderation\Repositories\ReviewModerationRepository;

class GetReviewReportDetailQuery
{
    public function __construct(
        protected ReviewModerationRepository $repository
    ) {}

    // Lấy chi tiết hồ sơ một báo cáo đánh giá và các báo cáo liên quan
    public function execute(int $reportId): array
    {
        $report = $this->repository->findReportById($reportId);
        $relatedReports = $this->repository->getRelatedReports($report->ma_danh_gia, $reportId);

        return [
            'report'         => $report,
            'relatedReports' => $relatedReports,
        ];
    }
}

