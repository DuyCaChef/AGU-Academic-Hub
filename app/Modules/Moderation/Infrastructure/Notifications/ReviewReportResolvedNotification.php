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
        $courseTitle = $course ? "{$course->ma_mon_hoc} - {$course->ten_mon_hoc}" : 'môn học';

        $message = $this->customMessage ?? "Báo cáo của bạn về bài đánh giá cho môn \"{$courseTitle}\" đã được Ban kiểm duyệt {$this->actionLabel}.";

        return new DatabaseMessage([
            'title'       => 'Kết quả xử lý báo cáo đánh giá',
            'message'     => $message,
            'report_id'   => $this->report->ma_bao_cao,
            'review_id'   => $this->report->ma_danh_gia,
            'action'      => $this->actionLabel,
        ]);
    }
}

