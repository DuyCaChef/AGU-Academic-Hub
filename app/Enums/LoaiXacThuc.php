<?php

namespace App\Enums;

enum LoaiXacThuc: string
{
    case SINH_VIEN = 'SINH_VIEN';
    case CUU_SINH_VIEN = 'CUU_SINH_VIEN';

    public function label(): string
    {
        return match ($this) {
            self::SINH_VIEN => 'Sinh viên',
            self::CUU_SINH_VIEN => 'Cựu sinh viên',
        };
    }
}
