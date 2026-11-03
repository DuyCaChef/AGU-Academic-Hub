<?php

namespace App\Modules\Administration\Domain\Services;

class AdminDashboardAlertEvaluator
{
    // Đánh giá và tổng hợp các cảnh báo nghiệp vụ khẩn cấp cần xử lý ngay trên Dashboard.
    public function evaluate(array $params): array
    {
        $alerts = [];

        // 1. Cảnh báo dung lượng lưu trữ (Critical / Warning)
        $storage = $params['storage'] ?? null;
        if (is_array($storage)) {
            $status = $storage['trang_thai'] ?? 'AN_TOAN';
            $percent = (float) ($storage['ty_le_su_dung_phan_tram'] ?? 0);
            $usedFormatted = $storage['tong_dung_luong_dinh_dang'] ?? '0 MB';
            $quotaFormatted = $storage['han_muc_luu_tru_dinh_dang'] ?? '0 MB';
            
            if ($status === 'NGUY_HIEM' || $percent >= 90) {
                $alerts[] = [
                    'id' => 'storage_critical',
                    'severity' => 'critical',
                    'title' => 'Dung lượng lưu trữ đạt mức nguy cấp',
                    'message' => "Hệ thống đã sử dụng {$percent}% ({$usedFormatted} / {$quotaFormatted}). Nguy cơ máy chủ từ chối tiếp nhận tài liệu mới.",
                    'count' => 1,
                    'actionLabel' => 'Dọn dẹp lưu trữ ngay',
                    'actionRoute' => 'admin.storage.index',
                ];
            } elseif ($status === 'CANH_BAO' || $percent >= 80) {
                $alerts[] = [
                    'id' => 'storage_warning',
                    'severity' => 'warning',
                    'title' => 'Cảnh báo dung lượng lưu trữ',
                    'message' => "Hệ thống đã sử dụng {$percent}% ({$usedFormatted} / {$quotaFormatted}). Cần theo dõi tốc độ tăng trưởng hoặc dọn dẹp các tệp tạm.",
                    'count' => 1,
                    'actionLabel' => 'Theo dõi dung lượng',
                    'actionRoute' => 'admin.storage.index',
                ];
            }
        }

        // 2. Báo cáo vi phạm đang chờ xử lý
        $pendingReports = (int) ($params['pendingReports'] ?? 0);
        if ($pendingReports > 0) {
            $severity = $pendingReports >= 5 ? 'critical' : 'warning';
            $alerts[] = [
                'id' => 'pending_reports',
                'severity' => $severity,
                'title' => 'Báo cáo vi phạm chưa xử lý',
                'message' => "Có {$pendingReports} báo cáo vi phạm nội dung đang chờ kiểm duyệt viên xử lý để đảm bảo môi trường học thuật.",
                'count' => $pendingReports,
                'actionLabel' => 'Xử lý báo cáo',
                'actionRoute' => 'admin.reports.index',
            ];
        }

        // 3. Yêu cầu gỡ tài liệu đang chờ giải quyết
        $pendingRemovalRequests = (int) ($params['pendingRemovalRequests'] ?? 0);
        if ($pendingRemovalRequests > 0) {
            $alerts[] = [
                'id' => 'pending_removal_requests',
                'severity' => 'warning',
                'title' => 'Yêu cầu gỡ tài liệu chờ xử lý',
                'message' => "Có {$pendingRemovalRequests} yêu cầu gỡ bỏ tài liệu học thuật từ người dùng hoặc tác giả đang chờ giải quyết.",
                'count' => $pendingRemovalRequests,
                'actionLabel' => 'Xét duyệt yêu cầu',
                'actionRoute' => 'admin.documents.removal-requests.index',
            ];
        }

        // 4. Tài liệu đang chờ kiểm tra / tạm ẩn
        $documentsToReview = (int) ($params['documentsToReview'] ?? 0);
        if ($documentsToReview > 0) {
            $severity = $documentsToReview >= 10 ? 'critical' : 'warning';
            $alerts[] = [
                'id' => 'documents_to_review',
                'severity' => $severity,
                'title' => 'Tài liệu đang chờ kiểm tra',
                'message' => "Có {$documentsToReview} tài liệu học thuật bị tạm ẩn hoặc đang nằm trong hàng đợi kiểm duyệt trước khi công khai.",
                'count' => $documentsToReview,
                'actionLabel' => 'Kiểm tra tài liệu',
                'actionRoute' => 'admin.documents.review-queue',
            ];
        }

        // 5. Hồ sơ KYC Cựu sinh viên chờ duyệt
        $pendingAlumniKyc = (int) ($params['pendingAlumniKyc'] ?? ($params['pendingKyc'] ?? 0));
        if ($pendingAlumniKyc > 0) {
            $alerts[] = [
                'id' => 'pending_kyc',
                'severity' => 'info',
                'title' => 'Hồ sơ KYC cựu sinh viên chờ duyệt',
                'message' => "Có {$pendingAlumniKyc} hồ sơ xác thực cựu sinh viên đang chờ ban quản trị phê duyệt danh tính để mở khóa tài khoản.",
                'count' => $pendingAlumniKyc,
                'actionLabel' => 'Duyệt hồ sơ KYC',
                'actionRoute' => 'admin.kyc.index',
            ];
        }

        // 6. Tài liệu thùng rác quá hạn lưu 60 ngày
        $expiredTrashCount = (int) ($params['expiredTrashedDocuments'] ?? 0);
        if ($expiredTrashCount > 0) {
            $alerts[] = [
                'id' => 'expired_trash',
                'severity' => 'warning',
                'title' => 'Tài liệu thùng rác đã quá hạn',
                'message' => "Có {$expiredTrashCount} tài liệu trong thùng rác đã quá hạn lưu trữ 60 ngày cần được dọn dẹp vĩnh viễn để giải phóng dung lượng đĩa.",
                'count' => $expiredTrashCount,
                'actionLabel' => 'Dọn dẹp thùng rác',
                'actionRoute' => 'admin.storage.index',
            ];
        }

        // 7. Tài khoản bị tạm khóa do vi phạm
        $lockedUsers = (int) ($params['lockedUsers'] ?? 0);
        if ($lockedUsers > 0) {
            $alerts[] = [
                'id' => 'locked_users',
                'severity' => 'info',
                'title' => 'Tài khoản đang bị tạm khóa',
                'message' => "Có {$lockedUsers} tài khoản người dùng đang trong trạng thái tạm khóa vi phạm quy định cộng đồng.",
                'count' => $lockedUsers,
                'actionLabel' => 'Quản lý tài khoản',
                'actionRoute' => 'admin.users.index',
            ];
        }

        // Sắp xếp thứ tự ưu tiên: critical -> warning -> info
        $severityWeight = ['critical' => 1, 'warning' => 2, 'info' => 3];
        usort($alerts, function ($a, $b) use ($severityWeight) {
            return ($severityWeight[$a['severity']] ?? 99) <=> ($severityWeight[$b['severity']] ?? 99);
        });

        return $alerts;
    }
}
