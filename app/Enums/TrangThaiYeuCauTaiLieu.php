<?php

namespace App\Enums;

enum TrangThaiYeuCauTaiLieu: string
{
    case MO = 'MO';
    case CHO_GIAI_NGAN = 'CHO_GIAI_NGAN';
    case DA_HOAN_THANH = 'DA_HOAN_THANH';
    case DONG = 'DONG';
    case DA_HUY = 'DA_HUY';

    public function label(): string
    {
        return match ($this) {
            self::MO => 'Đang mở tìm kiếm',
            self::CHO_GIAI_NGAN => 'Chờ Admin/Mod duyệt giải ngân',
            self::DA_HOAN_THANH => 'Đã giải quyết',
            self::DONG => 'Đã đóng',
            self::DA_HUY => 'Đã hủy',
        };
    }
}
