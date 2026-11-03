<?php

namespace App\Modules\Identity\Infrastructure\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class FirstTimePasswordChangeRequiredNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $mssv
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    // Gửi tới Sinh viên lần đầu đăng nhập, nhắc nhở đổi mật khẩu 
    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'title' => 'Thông báo yêu cầu đổi mật khẩu lần đầu',
            'message' => 'Chào mừng bạn đến với AGU Academic Hub. Để bảo mật tài khoản, bạn được yêu cầu đổi mật khẩu khởi tạo trong lần đăng nhập đầu tiên.',
            'mssv' => $this->mssv,
            'action_url' => route('password.change'),
        ]);
    }
}

