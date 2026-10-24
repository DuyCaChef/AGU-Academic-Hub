<?php

namespace App\Enums;

enum TrangThaiYeuCauTaiLieu: string
{
    case MO = 'MO';
    case DA_HOAN_THANH = 'DA_HOAN_THANH';
    case DONG = 'DONG';
    case DA_HUY = 'DA_HUY';

    public function label(): string
    {
        return match ($this) {
            self::MO => 'Đang mở tìm kiếm',
            self::DA_HOAN_THANH => 'Đã giải quyết',
            self::DONG => 'Đã đóng',
            self::DA_HUY => 'Đã hủy',
        };
    }
}
