<?php

namespace App\Modules\Moderation\Infrastructure\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

// Thông báo gửi tới người tạo yêu cầu Bounty khi bài nộp bị tự động từ chối do tài liệu vi phạm bị xóa
class BountySubmissionCancelledNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly int $maYeuCau,
        private readonly string $documentTitle,
        private readonly string $reason
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'title'      => 'Bài đóng góp cho yêu cầu Bounty bị từ chối',
            'message'    => "Bài đóng góp có tài liệu \"{$this->documentTitle}\" cho yêu cầu #{$this->maYeuCau} của bạn đã bị từ chối do tài liệu bị Ban kiểm duyệt gỡ bỏ. Lý do: {$this->reason}. Yêu cầu của bạn đã được mở lại để nhận tài liệu khác.",
            'ma_yeu_cau' => $this->maYeuCau,
            'url'        => route('document-requests.show', $this->maYeuCau),
        ]);
    }
}

