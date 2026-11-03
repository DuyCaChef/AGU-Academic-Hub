<?php

namespace App\Modules\Administration\Domain\Enums;

enum LoaiDoiTuongKiemToan: string
{
    case NGUOI_DUNG = 'NGUOI_DUNG';
    case SINH_VIEN_RAW = 'SINH_VIEN_RAW';
    case YEU_CAU_XAC_THUC = 'YEU_CAU_XAC_THUC';
    case BAO_CAO_VI_PHAM = 'BAO_CAO_VI_PHAM';
    case TAI_LIEU = 'TAI_LIEU';
    case KHOA = 'KHOA';
    case CHUYEN_NGANH = 'CHUYEN_NGANH';
    case MON_HOC = 'MON_HOC';
    case CAU_HINH_HE_THONG = 'CAU_HINH_HE_THONG';
    case PHIEN_LAM_VIEC = 'PHIEN_LAM_VIEC';

    public function label(): string
    {
        return match ($this) {
            self::NGUOI_DUNG => 'Tài khoản người dùng',
            self::SINH_VIEN_RAW => 'Dữ liệu sinh viên thô',
            self::YEU_CAU_XAC_THUC => 'Hồ sơ KYC',
            self::BAO_CAO_VI_PHAM => 'Báo cáo vi phạm',
            self::TAI_LIEU => 'Tài liệu học thuật',
            self::KHOA => 'Khoa / Đơn vị',
            self::CHUYEN_NGANH => 'Chuyên ngành đào tạo',
            self::MON_HOC => 'Môn học / Học phần',
            self::CAU_HINH_HE_THONG => 'Cấu hình hệ thống',
            self::PHIEN_LAM_VIEC => 'Phiên đăng nhập / đăng xuất',
        };
    }
}

