<?php

namespace App\Enums;

enum LoaiGiaoDichDiem: string
{
    case CONG_DIEM = 'CONG_DIEM';
    case TAM_GIU = 'TAM_GIU';
    case GIAI_PHONG = 'GIAI_PHONG';
    case SU_DUNG = 'SU_DUNG';
    case HOAN_TRA = 'HOAN_TRA';

    public function label(): string
    {
        return match ($this) {
            self::CONG_DIEM => 'Cộng điểm thưởng',
            self::TAM_GIU => 'Tạm giữ điểm',
            self::GIAI_PHONG => 'Giải phóng điểm',
            self::SU_DUNG => 'Sử dụng điểm',
            self::HOAN_TRA => 'Hoàn trả điểm',
        };
    }
}
