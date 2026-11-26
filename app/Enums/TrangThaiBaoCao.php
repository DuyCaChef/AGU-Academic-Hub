<?php

namespace App\Enums;

// Định nghĩa trạng thái xử lý báo cáo vi phạm cộng đồng
enum TrangThaiBaoCao: string
{
    case CHO_XU_LY = 'CHO_XU_LY';
    case DA_XU_LY = 'DA_XU_LY';
    case BAC_BO = 'BAC_BO';

    // Nhãn hiển thị tiếng Việt trên giao diện
    public function label(): string
    {
        return match ($this) {
            self::CHO_XU_LY => 'Chờ xử lý',
            self::DA_XU_LY => 'Đã xử lý',
            self::BAC_BO => 'Bác bỏ',
        };
    }
}

