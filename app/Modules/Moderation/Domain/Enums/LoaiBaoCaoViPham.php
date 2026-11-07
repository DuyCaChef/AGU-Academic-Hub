<?php

namespace App\Modules\Moderation\Domain\Enums;

// Enum định nghĩa các loại đối tượng nội dung bị sinh viên báo cáo vi phạm tiêu chuẩn cộng đồng
enum LoaiBaoCaoViPham: string
{
    // Báo cáo bài đánh giá môn học (nội dung học phần, bài tập, đề thi)
    case MON_HOC = 'MON_HOC';

    // Báo cáo bài đánh giá công kích, bôi nhọ giảng viên phụ trách
    case GIANG_VIEN = 'GIANG_VIEN';

    // Báo cáo tài liệu học tập vi phạm bản quyền, lộ đề thi, mã độc hại
    case TAI_LIEU = 'TAI_LIEU';

    // Nhãn hiển thị tiếng Việt trên giao diện quản trị
    public function label(): string
    {
        return match ($this) {
            self::MON_HOC    => 'Đánh giá Môn học',
            self::GIANG_VIEN => 'Đánh giá Giảng viên',
            self::TAI_LIEU   => 'Tài liệu học thuật',
        };
    }

    // Mã màu Tailwind đặc trưng cho từng loại đối tượng vi phạm
    public function badgeClasses(): string
    {
        return match ($this) {
            self::MON_HOC    => 'border-indigo-200 bg-indigo-50 text-indigo-700',
            self::GIANG_VIEN => 'border-amber-200 bg-amber-50 text-amber-800',
            self::TAI_LIEU   => 'border-blue-200 bg-blue-50 text-blue-700',
        };
    }
}

