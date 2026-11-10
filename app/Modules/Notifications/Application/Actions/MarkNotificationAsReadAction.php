<?php

namespace App\Modules\Notifications\Application\Actions;

use App\Models\NguoiDung;
use App\Models\ThongBao;

class MarkNotificationAsReadAction
{
    // Đánh dấu một thông báo là đã đọc (Đảm bảo Idempotent & Quyền sở hữu)
    public function execute(NguoiDung $user, string $notificationId): bool
    {
        /** @var ThongBao|null $notification */
        $notification = $user->notifications()
            ->where('id', $notificationId)
            ->first();

        if (!$notification) {
            return false;
        }

        if ($notification->unread()) {
            $notification->markAsRead();
        }

        return true;
    }
}

