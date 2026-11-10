<?php

namespace App\Modules\Notifications\Infrastructure\Listeners;

use App\Modules\Notifications\Application\Contracts\NotificationDispatcherInterface;
use App\Modules\Notifications\Domain\Events\ReviewReportedEvent;
use App\Modules\Notifications\Domain\Services\NotificationPayloadBuilder;
use App\Modules\Notifications\Domain\Services\NotificationRecipientPolicy;

class ReviewNotificationListener
{
    public function __construct(
        protected NotificationDispatcherInterface $dispatcher,
        protected NotificationPayloadBuilder $payloadBuilder,
        protected NotificationRecipientPolicy $recipientPolicy
    ) {}

    // Xử lý thông báo khi đánh giá môn học bị báo cáo vi phạm (UC-MOD-03)
    public function handle(ReviewReportedEvent $event): void
    {
        $review = $event->review;
        $courseName = $review->monHoc?->ten_mon_hoc ?? 'Môn học';
        $reportCount = $review->baoCaoDanhGias()->count();

        // Gửi thông báo cho Kiểm duyệt viên (đảm bảo không lộ danh tính của tác giả review ẩn danh)
        $moderators = $this->recipientPolicy->getModeratorsForReviewReports();
        $payload = $this->payloadBuilder->reviewReported($review->ma_danh_gia, $courseName, $reportCount);

        $this->dispatcher->dispatchMultiple($moderators, $payload);
    }
}

