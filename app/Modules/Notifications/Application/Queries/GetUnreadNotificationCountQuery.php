<?php

namespace App\Modules\Notifications\Application\Queries;

use App\Models\NguoiDung;

class GetUnreadNotificationCountQuery
{
    // Đếm số lượng thông báo chưa đọc của người dùng hiện tại (Truy vấn tối ưu)
    public function execute(NguoiDung $user): int
    {
        return $user->unreadNotifications()->count();
    }
}

