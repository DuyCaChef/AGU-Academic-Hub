<?php

namespace App\Modules\Moderation\Infrastructure\Notifications;

use App\Models\BaoCaoDanhGia;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class ReviewReportResolvedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly BaoCaoDanhGia $report,
        private readonly string $actionLabel,
        private readonly ?string $customMessage = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        $review = $this->report->danhGia;
        $course = $review?->monHoc;
        $isLecturer = ($this->report->loai_doi_tuong === 'GIANG_VIEN') || ($this->report->ly_do === 'BOI_NHO');
        $lecturerName = $this->report->ten_giang_vien;
        $courseTitle = $course ? "{$course->ma_mon_hoc} - {$course->ten_mon_hoc}" : null;

        $targetText = match (true) {
            $isLecturer && $lecturerName && $courseTitle => "giảng viên {$lecturerName} (môn {$courseTitle})",
            $isLecturer && $lecturerName => "giảng viên {$lecturerName}",
            !empty($courseTitle) => "môn \"{$courseTitle}\"",
            default => 'nội dung đánh giá',
        };

        $message = $this->customMessage ?? "Báo cáo của bạn về bài đánh giá liên quan đến {$targetText} đã được Ban kiểm duyệt {$this->actionLabel}.";

        return new DatabaseMessage([
            'title'       => 'Kết quả xử lý báo cáo đánh giá',
            'message'     => $message,
            'report_id'   => $this->report->ma_bao_cao,
            'review_id'   => $this->report->ma_danh_gia,
            'action'      => $this->actionLabel,
        ]);
    }
}

