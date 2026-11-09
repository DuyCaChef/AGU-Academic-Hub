<?php

namespace App\Modules\Moderation\Infrastructure\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

// Thông báo gửi tới người đóng góp (SV B) khi tài liệu đã giải ngân bị gỡ bỏ do vi phạm bản quyền và bị thu hồi điểm thưởng
class BountyPayoutClawedBackNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly int $maYeuCau,
        private readonly string $documentTitle,
        private readonly int $diemThuHoi,
        private readonly string $reason
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'title'      => 'Thu hồi điểm thưởng Bounty do vi phạm bản quyền',
            'message'    => "Tài liệu \"{$this->documentTitle}\" bạn nộp cho yêu cầu #{$this->maYeuCau} đã bị Ban kiểm duyệt gỡ bỏ do vi phạm bản quyền/quy định ({$this->reason}). Hệ thống đã thu hồi {$this->diemThuHoi} điểm thưởng đã giải ngân từ ví của bạn.",
            'ma_yeu_cau' => $this->maYeuCau,
            'url'        => route('wallet.index'),
        ]);
    }
}

