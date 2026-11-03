<?php

namespace App\Enums;

// Định nghĩa trạng thái tài liệu học thuật thống nhất toàn hệ thống
enum TrangThaiTaiLieu: string
{
    case CHO_DUYET = 'CHO_DUYET';
    case DA_DUYET = 'DA_DUYET';
    case CHO_KIEM_TRA = 'CHO_KIEM_TRA';
    case TAM_AN_KIEM_TRA = 'TAM_AN_KIEM_TRA';
    case TU_CHOI = 'TU_CHOI';

    // Nhãn hiển thị tiếng Việt trên giao diện
    public function label(): string
    {
        return match ($this) {
            self::CHO_DUYET => 'Chờ duyệt',
            self::DA_DUYET => 'Đã duyệt',
            self::CHO_KIEM_TRA => 'Chờ kiểm tra',
            self::TAM_AN_KIEM_TRA => 'Tạm ẩn kiểm tra',
            self::TU_CHOI => 'Từ chối',
        };
    }
}

