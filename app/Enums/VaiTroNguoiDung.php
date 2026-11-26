<?php

namespace App\Enums;

enum VaiTroNguoiDung: string
{
    case SINH_VIEN = 'SINH_VIEN';
    case CUU_SINH_VIEN = 'CUU_SINH_VIEN';
    case KIEM_DUYET_VIEN = 'KIEM_DUYET_VIEN';
    case QUAN_TRI_VIEN = 'QUAN_TRI_VIEN';

    public function label(): string
    {
        return match ($this) {
            self::SINH_VIEN => 'Sinh viên',
            self::CUU_SINH_VIEN => 'Cựu sinh viên',
            self::KIEM_DUYET_VIEN => 'Kiểm duyệt viên',
            self::QUAN_TRI_VIEN => 'Quản trị viên',
        };
    }
}
