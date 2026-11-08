<?php

namespace App\Modules\Administration\Application\Queries;

use App\Modules\Administration\Domain\Services\AdminDashboardAlertEvaluator;
use App\Modules\Administration\Infrastructure\Persistence\AdminDashboardStatsRepository;

class GetAdminDashboardOverviewQuery
{
    public function __construct(
        protected AdminDashboardStatsRepository $statsRepository,
        protected GetStorageOverviewQuery $storageQuery,
        protected AdminDashboardAlertEvaluator $alertEvaluator
    ) {}

    // Thu thập toàn bộ các chỉ số thống kê tổng quan của Bảng điều khiển Quản trị
    public function execute(): array
    {
        $storageStats = $this->storageQuery->getTotalStorageStats();
        $userStats = $this->statsRepository->getUserStatusStats();
        $rawStudentStats = $this->statsRepository->getRawStudentStats();
        $kycStats = $this->statsRepository->getKycOverviewStats();
        $catalogStats = $this->statsRepository->getAcademicCatalogStats();
        $documentStats = $this->statsRepository->getDocumentOverviewStats();

        $pendingKyc = $kycStats['pending'];
        $pendingReports = $this->statsRepository->getPendingReportsCount();
        $pendingReviewReports = $this->statsRepository->getPendingReviewReportsCount();
        $documentsToReview = $this->statsRepository->getDocumentsToReviewCount();
        $pendingRemovalRequests = $this->statsRepository->getPendingRemovalRequestsCount();
        $trashedDocuments = $this->statsRepository->getTrashedDocumentsCount();
        $expiredTrashedDocuments = $this->statsRepository->getExpiredTrashedDocumentsCount();
        $pendingBountyPayouts = $this->statsRepository->getPendingBountyPayoutsCount();

        // Đánh giá các cảnh báo nghiệp vụ cần xử lý ngay
        $alerts = $this->alertEvaluator->evaluate([
            'storage'              => $storageStats,
            'pendingReports'       => $pendingReports,
            'pendingRemovalRequests' => $pendingRemovalRequests,
            'documentsToReview'    => $documentsToReview,
            'pendingKyc'           => $pendingKyc,
            'pendingAlumniKyc'     => $kycStats['pendingAlumni'],
            'expiredTrashedDocuments' => $expiredTrashedDocuments,
            'lockedUsers'          => $userStats['locked'],
            'pendingBountyPayouts' => $pendingBountyPayouts,
        ]);

        return [
            // Các trường gốc giữ nguyên tính tương thích ngược với API/Test hiện tại
            'pendingKyc' => $pendingKyc,
            'pendingReports' => $pendingReports,
            'pendingReviewReports' => $pendingReviewReports,
            'documentsToReview' => $documentsToReview,
            'pendingRemovalRequests' => $pendingRemovalRequests,
            'totalUsers' => $userStats['total'],
            'totalDocuments' => $documentStats['total'],
            'storage' => $storageStats,

            // Cấu trúc phân nhóm chi tiết nghiệp vụ Dashboard
            'users' => $userStats,
            'rawStudents' => $rawStudentStats,
            'kyc' => $kycStats,
            'moderation' => [
                'pendingReports' => $pendingReports,
                'pendingReviewReports' => $pendingReviewReports,
                'documentsToReview' => $documentsToReview,
                'pendingRemovalRequests' => $pendingRemovalRequests,
                'trashedDocuments' => $trashedDocuments,
                'expiredTrashedDocuments' => $expiredTrashedDocuments,
            ],
            'catalog' => $catalogStats,
            'documents' => $documentStats,
            'alerts' => $alerts,
            'generatedAt' => now()->toIso8601String(),
        ];
    }
}
