<?php

namespace App\Modules\Bounties\Infrastructure\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

// Thông báo gửi đến Admin/Kiểm duyệt viên khi sinh viên xác nhận bài nộp - chờ duyệt giải ngân
class BountyPayoutApprovalRequiredNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly int $maYeuCau,
        private readonly int $maDapUng,
        private readonly int $diemThuong,
        private readonly string $tenNguoiYeuCau,
        private readonly string $tenNguoiDongGop
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    // Nội dung thông báo gửi đến Admin/Mod về yêu cầu duyệt giải ngân điểm Bounty
    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'title'           => 'Yêu cầu duyệt giải ngân điểm Bounty',
            'message'         => "{$this->tenNguoiYeuCau} đã xác nhận bài đóng góp của {$this->tenNguoiDongGop} cho yêu cầu tài liệu #{$this->maYeuCau}. Cần duyệt để giải ngân {$this->diemThuong} điểm thưởng.",
            'ma_yeu_cau'      => $this->maYeuCau,
            'ma_dap_ung'      => $this->maDapUng,
            'diem_thuong'     => $this->diemThuong,
            'url'             => route('admin.bounties.payout-queue'),
        ]);
    }
}
