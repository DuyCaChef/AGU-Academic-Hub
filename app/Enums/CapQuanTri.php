<?php

namespace App\Enums;

enum CapQuanTri: string
{
    case THUONG = 'THUONG';
    case TOI_CAO = 'TOI_CAO';

    public function label(): string
    {
        return match ($this) {
            self::THUONG => 'Admin thường',
            self::TOI_CAO => 'Super Admin',
        };
    }
}
