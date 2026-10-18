<?php

namespace App\Enums;

enum UserRole: string
{
    case STUDENT = 'STUDENT';
    case ALUMNI = 'ALUMNI';
    case MODERATOR = 'MODERATOR';
    case ADMIN = 'ADMIN';

    public function label(): string
    {
        return match ($this) {
            self::STUDENT => 'Sinh viên',
            self::ALUMNI => 'Cựu sinh viên',
            self::MODERATOR => 'Kiểm duyệt viên',
            self::ADMIN => 'Quản trị viên',
        };
    }

}