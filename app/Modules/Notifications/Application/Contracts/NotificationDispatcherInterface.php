<?php

namespace App\Modules\Notifications\Application\Contracts;

use App\Models\NguoiDung;
use App\Modules\Notifications\Application\DTOs\NotificationPayload;

interface NotificationDispatcherInterface
{
    // Gửi thông báo tới một người nhận duy nhất
    public function dispatch(NguoiDung $recipient, NotificationPayload $payload): bool;

    // Gửi thông báo tới danh sách nhiều người nhận (ví dụ nhóm kiểm duyệt viên hoặc quản trị viên)
    public function dispatchMultiple(iterable $recipients, NotificationPayload $payload): int;
}

