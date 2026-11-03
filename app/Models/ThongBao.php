<?php

namespace App\Models;

use Illuminate\Notifications\DatabaseNotification as BaseDatabaseNotification;

class ThongBao extends BaseDatabaseNotification
{
    protected $table = 'notifications';

    protected static function booted(): void
    {
        static::creating(function (self $notification): void {
            if (empty($notification->title) && isset($notification->data['title'])) {
                $notification->title = (string) $notification->data['title'];
            }

            if (empty($notification->message) && isset($notification->data['message'])) {
                $notification->message = (string) $notification->data['message'];
            }
        });
    }

    // Tiêu đề thông báo 
    public function getTieuDeAttribute(): string
    {
        return $this->title ?? $this->data['title'] ?? 'Thông báo hệ thống';
    }

    // Nội dung thông báo
    public function getNoiDungAttribute(): string
    {
        return $this->message ?? $this->data['message'] ?? '';
    }

   // Đường dẫn liên kết của thông báo
    public function getDuongDanAttribute(): ?string
    {
        return $this->data['url'] ?? $this->data['action_url'] ?? null;
    }

    // Kiểm tra thông báo chưa đọc
    public function getLaChuaDocAttribute(): bool
    {
        return $this->unread();
    }
}

