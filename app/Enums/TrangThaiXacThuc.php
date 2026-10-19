<?php

namespace App\Enums;

enum TrangThaiXacThuc: string
{
    case CHO_DUYET = 'CHO_DUYET';
    case DA_DUYET = 'DA_DUYET';
    case TU_CHOI = 'TU_CHOI';

    public function label(): string
    {
        return match ($this) {
            self::CHO_DUYET => 'Chờ duyệt',
            self::DA_DUYET => 'Đã duyệt',
            self::TU_CHOI => 'Từ chối',
        };
    }
}
