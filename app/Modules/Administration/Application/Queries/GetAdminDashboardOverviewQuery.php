<?php

namespace App\Modules\Administration\Application\Queries;

use App\Modules\Administration\Infrastructure\Persistence\AdminDashboardStatsRepository;

class GetAdminDashboardOverviewQuery
{
    public function __construct(
        protected AdminDashboardStatsRepository $statsRepository,
        protected GetStorageOverviewQuery $storageQuery
    ) {}

    // Thu thập toàn bộ các chỉ số thống kê tổng quan của Bảng điều khiển Quản trị
    public function execute(): array
    {
        return [
            'pendingKyc' => $this->statsRepository->getPendingKycCount(),
            'pendingReports' => $this->statsRepository->getPendingReportsCount(),
            'documentsToReview' => $this->statsRepository->getDocumentsToReviewCount(),
            'pendingRemovalRequests' => $this->statsRepository->getPendingRemovalRequestsCount(),
            'totalUsers' => $this->statsRepository->getTotalUsersCount(),
            'totalDocuments' => $this->statsRepository->getTotalDocumentsCount(),
            'storage' => $this->storageQuery->getTotalStorageStats(),
        ];
    }
}

