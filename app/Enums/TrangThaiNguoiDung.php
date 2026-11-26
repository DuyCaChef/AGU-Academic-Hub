<?php

namespace App\Enums;

enum TrangThaiNguoiDung: string
{
    case CHUA_XAC_THUC = 'CHUA_XAC_THUC';
    case DANG_HOAT_DONG = 'DANG_HOAT_DONG';
    case TAM_KHOA = 'TAM_KHOA';

    public function label(): string
    {
        return match ($this) {
            self::CHUA_XAC_THUC => 'Chờ xác thực',
            self::DANG_HOAT_DONG => 'Đang hoạt động',
            self::TAM_KHOA => 'Tạm khóa',
        };
    }
}
