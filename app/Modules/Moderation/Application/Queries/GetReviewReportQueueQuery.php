<?php

namespace App\Modules\Moderation\Application\Queries;

use App\Modules\Moderation\Infrastructure\Persistence\ReviewModerationRepository;

class GetReviewReportQueueQuery
{
    public function __construct(
        protected ReviewModerationRepository $repository
    ) {}

    // Lấy dữ liệu phục vụ hiển thị hàng đợi báo cáo vi phạm toàn diện (Môn học, Giảng viên, Tài liệu)
    public function execute(array $filters = []): array
    {
        $reports = $this->repository->paginateUnifiedReports($filters, 15);
        $stats = $this->repository->getUnifiedStats();

        return [
            'reports' => $reports,
            'filters' => array_intersect_key($filters, array_flip(['type', 'status', 'reason', 'from', 'to', 'search'])),
            'reasons' => [
                // Lý do báo cáo đánh giá (Môn học & Giảng viên)
                'NGON_TU_KICH_DONG',
                'BOI_NHO',
                'QUAY_ROI',
                'SPAM',
                // Lý do báo cáo tài liệu
                'BAN_QUYEN',
                'SAI_NOI_DUNG',
                'MA_DOC',
                'TRUNG_LAP',
                // Chung
                'KHAC',
            ],
            'stats'   => $stats,
        ];
    }
}
