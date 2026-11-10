<?php

namespace App\Modules\Notifications\Application\Actions;

use App\Models\NguoiDung;
use App\Models\ThongBao;

class DeleteNotificationAction
{
    // Xóa một thông báo thuộc quyền sở hữu của người dùng hiện tại
    public function execute(NguoiDung $user, string $notificationId): bool
    {
        /** @var ThongBao|null $notification */
        $notification = $user->notifications()
            ->where('id', $notificationId)
            ->first();

        if (!$notification) {
            return false;
        }

        // Xóa bản ghi notification (Hard delete tiêu chuẩn của Laravel Notifications)
        // Không tác động đến bất kỳ thực thể nghiệp vụ liên quan nào
        $notification->delete();

        return true;
    }
}

