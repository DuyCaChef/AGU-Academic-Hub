<?php

namespace App\Modules\Notifications\Application\Actions;

use App\Models\NguoiDung;

class MarkAllNotificationsAsReadAction
{
    // Đánh dấu tất cả thông báo của người dùng là đã đọc (Single SQL Update tối ưu)
    public function execute(NguoiDung $user): int
    {
        return $user->unreadNotifications()->update([
            'da_doc_luc' => now(),
        ]);
    }
}

