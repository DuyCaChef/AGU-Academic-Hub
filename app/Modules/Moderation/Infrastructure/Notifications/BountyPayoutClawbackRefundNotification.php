<?php

namespace App\Modules\Moderation\Infrastructure\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

// Thông báo gửi tới người tạo yêu cầu Bounty (SV A) khi tài liệu đã giải ngân bị gỡ bỏ do vi phạm và được hoàn điểm đền bù
class BountyPayoutClawbackRefundNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly int $maYeuCau,
        private readonly string $documentTitle,
        private readonly int $diemHoanTra,
        private readonly string $reason
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'title'      => 'Hoàn trả điểm thưởng Bounty do tài liệu vi phạm bản quyền',
            'message'    => "Tài liệu \"{$this->documentTitle}\" bạn đã xác nhận cho yêu cầu #{$this->maYeuCau} đã bị Ban kiểm duyệt gỡ bỏ do vi phạm bản quyền/quy định ({$this->reason}). Hệ thống đã hoàn trả đền bù {$this->diemHoanTra} điểm vào ví của bạn.",
            'ma_yeu_cau' => $this->maYeuCau,
            'url'        => route('wallet.index'),
        ]);
    }
}

