<?php

namespace App\Modules\Moderation\Application\Queries;

use App\Modules\Moderation\Infrastructure\Persistence\ModerationReportRepository;

class GetReportDetailQuery
{
    public function __construct(
        protected ModerationReportRepository $repository
    ) {}

    // Lấy chi tiết một báo cáo và các báo cáo liên quan của cùng tài liệu
    public function execute(int $reportId): array
    {
        $report = $this->repository->findReportById($reportId);
        $relatedReports = $this->repository->getRelatedReports((int) $report->ma_tai_lieu, $reportId);

        return [
            'report'         => $report,
            'relatedReports' => $relatedReports,
        ];
    }
}
