<?php

namespace App\Modules\Bounties\Infrastructure\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

// Thông báo gửi đến người đóng góp khi Admin/Mod duyệt giải ngân điểm Bounty
class BountyPayoutApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly int $maYeuCau,
        private readonly int $diemThuong
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    // Thông báo người đóng góp nhận được điểm thưởng sau khi Admin/Mod duyệt
    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'title'       => 'Điểm thưởng Bounty đã được giải ngân',
            'message'     => "Chúc mừng! Tài liệu đóng góp của bạn cho yêu cầu #{$this->maYeuCau} đã được Ban kiểm duyệt xác nhận hợp lệ. Bạn nhận được {$this->diemThuong} điểm thưởng.",
            'ma_yeu_cau'  => $this->maYeuCau,
            'diem_thuong' => $this->diemThuong,
            'url'         => route('wallet.index'),
        ]);
    }
}
