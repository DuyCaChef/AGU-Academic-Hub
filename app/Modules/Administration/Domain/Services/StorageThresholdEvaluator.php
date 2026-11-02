<?php

namespace App\Modules\Administration\Domain\Services;

class StorageThresholdEvaluator
{
    // Xác định mã trạng thái hệ thống: AN_TOAN, CANH_BAO, NGUY_HIEM
    public function determineStatus(float $usedPercent, float $warningPercent, float $criticalPercent): string
    {
        if ($usedPercent >= $criticalPercent) {
            return 'NGUY_HIEM';
        }

        if ($usedPercent >= $warningPercent) {
            return 'CANH_BAO';
        }

        return 'AN_TOAN';
    }

    // Đánh giá mức độ cảnh báo vượt ngưỡng dung lượng và đề xuất hành động
    public function evaluateThresholdAlert(
        float $usedPercent,
        float $warningPercent,
        float $criticalPercent,
        string $usedFormatted,
        string $quotaFormatted
    ): array {
        $severity = 'safe';
        $title = 'Hệ thống lưu trữ đang trong giới hạn an toàn';
        $message = "Dung lượng hiện tại đang chiếm {$usedPercent}% so với hạn mức cho phép ({$usedFormatted} / {$quotaFormatted}).";
        $recommendations = [];

        if ($usedPercent >= $criticalPercent) {
            $severity = 'critical';
            $title = 'CẢNH BÁO NGUY CẤP: Dung lượng lưu trữ đã vượt ngưỡng đỏ!';
            $message = "Dung lượng đã sử dụng {$usedPercent}% (vượt ngưỡng đỏ {$criticalPercent}%). Hệ thống có nguy cơ từ chối tải lên tệp mới nếu không được giải phóng kịp thời.";
            $recommendations = [
                'Thực hiện ngay tác vụ dọn dẹp các tệp đã hết hạn lưu trong Thùng rác.',
                'Kiểm tra và xử lý danh sách tệp trùng lặp để tiết kiệm tài nguyên.',
                'Rà soát các tệp dung lượng lớn bất thường và liên hệ tác giả tối ưu hóa file nén/PDF.',
                'Xem xét nâng cấp hạn mức ổ đĩa máy chủ lưu trữ (STORAGE_QUOTA_MB).',
            ];
        } elseif ($usedPercent >= $warningPercent) {
            $severity = 'warning';
            $title = 'CẢNH BÁO: Dung lượng lưu trữ sắp đạt ngưỡng tối đa';
            $message = "Dung lượng đã sử dụng {$usedPercent}% (vượt ngưỡng cảnh báo vàng {$warningPercent}%). Cần theo dõi sát tốc độ tăng trưởng lưu trữ.";
            $recommendations = [
                'Dọn dẹp các tệp lưu tạm trong thùng rác đã quá hạn lưu trữ.',
                'Xem xét rà soát các tệp tải lên có dung lượng lớn.',
            ];
        }

        return [
            'muc_do' => $severity, // safe, warning, critical
            'tieu_de' => $title,
            'thong_diep' => $message,
            'ty_le_hien_tai' => $usedPercent,
            'nguong_canh_bao_vang' => $warningPercent,
            'nguong_canh_bao_do' => $criticalPercent,
            'khuyen_nghi' => $recommendations,
            'can_canh_bao' => $severity !== 'safe',
        ];
    }

    // Đánh giá mức độ cảnh báo kích thước của từng file lớn bất thường
    public function evaluateFileSeverity(float $sizeKb): string
    {
        if ($sizeKb >= 51200) { // >= 50 MB
            return 'critical';
        }

        return 'warning';
    }
}
