<?php

namespace App\Modules\Notifications\Domain\Enums;

enum NotificationCategory: string
{
    // Thông báo mang tính cá nhân (điểm, tài liệu, kết quả giao dịch của user)
    case PERSONAL = 'PERSONAL';

    // Thông báo mang tính tác vụ công việc (yêu cầu kiểm duyệt, duyệt KYC, xử lý vi phạm)
    case WORK_ITEM = 'WORK_ITEM';

    public function label(): string
    {
        return match ($this) {
            self::PERSONAL => 'Cá nhân',
            self::WORK_ITEM => 'Tác vụ xử lý',
        };
    }
}

