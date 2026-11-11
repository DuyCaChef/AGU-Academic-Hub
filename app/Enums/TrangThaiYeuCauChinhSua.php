<?php

namespace App\Enums;

enum TrangThaiYeuCauChinhSua: string
{
    case CHO_XU_LY = 'CHO_XU_LY';
    case DANG_XU_LY = 'DANG_XU_LY';
    case TU_CHOI = 'TU_CHOI';
    case HOAN_THANH = 'HOAN_THANH';
    case DA_HUY = 'DA_HUY';

    public function label(): string
    {
        return match ($this) {
            self::CHO_XU_LY => 'Chờ người đóng góp phản hồi',
            self::DANG_XU_LY => 'Đang xử lý chỉnh sửa',
            self::TU_CHOI => 'Người đóng góp từ chối',
            self::HOAN_THANH => 'Đã hoàn tất chỉnh sửa',
            self::DA_HUY => 'Đã hủy',
        };
    }
}

