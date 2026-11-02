<?php

namespace App\Modules\Moderation\Application\Queries;

use App\Modules\Moderation\Infrastructure\Persistence\ModerationReportRepository;

class GetReportQueueQuery
{
    public function __construct(
        protected ModerationReportRepository $repository
    ) {}

    // Lấy dữ liệu hàng đợi báo cáo vi phạm phục vụ Inertia Admin/Reports/Index
    public function execute(array $filters = []): array
    {
        $reports = $this->repository->paginateReports($filters, 15);

        return [
            'reports' => $reports,
            'filters' => array_intersect_key($filters, array_flip(['status', 'reason', 'from', 'to'])),
            'reasons' => ['TRUNG_LAP', 'SAI_NOI_DUNG', 'MA_DOC', 'BAN_QUYEN', 'KHAC'],
        ];
    }
}
