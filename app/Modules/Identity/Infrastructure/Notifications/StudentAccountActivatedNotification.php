<?php

namespace App\Modules\Identity\Infrastructure\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class StudentAccountActivatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $mssv,
        private readonly string $hoTen
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    // Gửi tới Sinh viên khi tài khoản được kích hoạt thành công
    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'title' => 'Thông báo kích hoạt tài khoản thành công',
            'message' => "Chúc mừng {$this->hoTen}! Tài khoản sinh viên của bạn đã được kích hoạt thành công. Bạn hiện đã có thể truy cập toàn bộ tài liệu học thuật.",
            'mssv' => $this->mssv,
            'activated_at' => now()->toIso8601String(),
        ]);
    }
}

