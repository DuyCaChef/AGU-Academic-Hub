<?php

namespace App\Modules\Bounties\Infrastructure\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

// Thông báo gửi đến người liên quan khi Admin/Mod từ chối giải ngân điểm Bounty
class BountyPayoutRejectedNotification extends Notification
{
    use Queueable;

    // $recipient: 'nguoi_yeu_cau' | 'nguoi_dong_gop'
    public function __construct(
        private readonly int $maYeuCau,
        private readonly int $diemThuong,
        private readonly string $lyDo,
        private readonly string $recipient
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    // Thông báo từ chối giải ngân - nội dung khác nhau cho người yêu cầu và người đóng góp
    public function toDatabase(object $notifiable): DatabaseMessage
    {
        if ($this->recipient === 'nguoi_yeu_cau') {
            return new DatabaseMessage([
                'title'      => 'Bài đóng góp bị từ chối bởi Admin',
                'message'    => "Bài đóng góp bạn xác nhận cho yêu cầu #{$this->maYeuCau} đã bị Admin từ chối. Lý do: {$this->lyDo}. Điểm thưởng {$this->diemThuong} điểm đã được hoàn trả vào ví của bạn. Yêu cầu đã được mở lại.",
                'ma_yeu_cau' => $this->maYeuCau,
                'url'        => route('document-requests.show', $this->maYeuCau),
            ]);
        }

        return new DatabaseMessage([
            'title'      => 'Bài đóng góp của bạn bị Admin từ chối giải ngân',
            'message'    => "Bài đóng góp của bạn cho yêu cầu #{$this->maYeuCau} đã bị từ chối duyệt giải ngân. Lý do: {$this->lyDo}.",
            'ma_yeu_cau' => $this->maYeuCau,
            'url'        => route('document-requests.show', $this->maYeuCau),
        ]);
    }
}
