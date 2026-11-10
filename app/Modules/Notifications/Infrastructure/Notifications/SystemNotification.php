<?php

namespace App\Modules\Notifications\Infrastructure\Notifications;

use App\Modules\Notifications\Application\DTOs\NotificationPayload;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class SystemNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly NotificationPayload $payload
    ) {}

    // Kênh phân phối thông báo (mặc định lưu vào database)
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    // Định dạng dữ liệu lưu trữ trong bảng notifications
    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage($this->payload->toArray());
    }
}

