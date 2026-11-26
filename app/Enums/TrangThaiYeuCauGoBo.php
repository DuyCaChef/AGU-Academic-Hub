<?php

namespace App\Enums;

// Định nghĩa trạng thái yêu cầu gỡ bỏ tài liệu sở hữu cộng đồng
enum TrangThaiYeuCauGoBo: string
{
    case CHO_XU_LY = 'CHO_XU_LY';
    case DA_DUYET = 'DA_DUYET';
    case TU_CHOI = 'TU_CHOI';

    // Nhãn hiển thị tiếng Việt trên giao diện
    public function label(): string
    {
        return match ($this) {
            self::CHO_XU_LY => 'Chờ xử lý',
            self::DA_DUYET => 'Đã duyệt',
            self::TU_CHOI => 'Từ chối',
        };
    }
}

