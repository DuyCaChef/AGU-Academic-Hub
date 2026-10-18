<?php

namespace App\Enums;

enum UserStatus: string
{
    case UNVERIFIED = 'UNVERIFIED';
    case ACTIVE = 'ACTIVE';
    case SUSPENDED = 'SUSPENDED';

    function label(): string
    {
        return match ($this) {
            self::UNVERIFIED => 'Chờ xác thực',
            self::ACTIVE => 'Đang hoạt động',
            self::SUSPENDED => 'Tạm khóa',
        };
    }
}