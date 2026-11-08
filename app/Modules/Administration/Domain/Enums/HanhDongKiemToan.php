<?php

namespace App\Modules\Administration\Domain\Enums;

enum HanhDongKiemToan: string
{
    // Xác thực & Phiên làm việc
    case DANG_NHAP_ADMIN = 'DANG_NHAP_ADMIN';
    case DANG_XUAT_ADMIN = 'DANG_XUAT_ADMIN';

    // Dữ liệu sinh viên thô (Raw Roster)
    case NAP_SINH_VIEN_RAW = 'NAP_SINH_VIEN_RAW';
    case CAP_NHAT_SINH_VIEN_RAW = 'CAP_NHAT_SINH_VIEN_RAW';
    case XOA_SINH_VIEN_RAW = 'XOA_SINH_VIEN_RAW';

    // Xác thực KYC Cựu sinh viên
    case XEM_HO_SO_KYC = 'XEM_HO_SO_KYC';
    case DUYET_KYC = 'DUYET_KYC';
    case TU_CHOI_KYC = 'TU_CHOI_KYC';

    // Quản trị người dùng & Phân quyền
    case KHOA_TAI_KHOAN = 'KHOA_TAI_KHOAN';
    case MO_KHOA_TAI_KHOAN = 'MO_KHOA_TAI_KHOAN';
    case THAY_DOI_VAI_TRO = 'THAY_DOI_VAI_TRO';
    case GHI_NHAN_VI_PHAM = 'GHI_NHAN_VI_PHAM';

    // Kiểm duyệt & Quản trị tài liệu
    case XU_LY_BAO_CAO = 'XU_LY_BAO_CAO';
    case AN_TAI_LIEU = 'AN_TAI_LIEU';
    case XOA_MEM_TAI_LIEU = 'XOA_MEM_TAI_LIEU';
    case KHOI_PHUC_TAI_LIEU = 'KHOI_PHUC_TAI_LIEU';
    case XOA_VINH_VIEN_TAI_LIEU = 'XOA_VINH_VIEN_TAI_LIEU';
    case DUYET_GIAI_NGAN_BOUNTY = 'DUYET_GIAI_NGAN_BOUNTY';
    case TU_CHOI_GIAI_NGAN_BOUNTY = 'TU_CHOI_GIAI_NGAN_BOUNTY';

    // Kiểm duyệt Đánh giá môn học
    case XU_LY_BAO_CAO_DANH_GIA = 'XU_LY_BAO_CAO_DANH_GIA';
    case AN_DANH_GIA = 'AN_DANH_GIA';
    case XOA_DANH_GIA = 'XOA_DANH_GIA';

    // Danh mục học thuật
    case THAY_DOI_KHOA = 'THAY_DOI_KHOA';
    case THAY_DOI_CHUYEN_NGANH = 'THAY_DOI_CHUYEN_NGANH';
    case THAY_DOI_MON_HOC = 'THAY_DOI_MON_HOC';

    // Cấu hình hệ thống & Lưu trữ
    case THAY_DOI_CAU_HINH = 'THAY_DOI_CAU_HINH';
    case DON_DEP_THUNG_RAC = 'DON_DEP_THUNG_RAC';

    public function label(): string
    {
        return match ($this) {
            self::DANG_NHAP_ADMIN => 'Đăng nhập khu vực Admin',
            self::DANG_XUAT_ADMIN => 'Đăng xuất khu vực Admin',
            self::NAP_SINH_VIEN_RAW => 'Nạp dữ liệu sinh viên thô',
            self::CAP_NHAT_SINH_VIEN_RAW => 'Cập nhật sinh viên thô',
            self::XOA_SINH_VIEN_RAW => 'Xóa sinh viên thô',
            self::XEM_HO_SO_KYC => 'Xem hồ sơ KYC cựu sinh viên',
            self::DUYET_KYC => 'Duyệt hồ sơ KYC',
            self::TU_CHOI_KYC => 'Từ chối hồ sơ KYC',
            self::KHOA_TAI_KHOAN => 'Khóa tài khoản',
            self::MO_KHOA_TAI_KHOAN => 'Mở khóa tài khoản',
            self::THAY_DOI_VAI_TRO => 'Thay đổi vai trò / Phân quyền',
            self::GHI_NHAN_VI_PHAM => 'Ghi nhận vi phạm người dùng',
            self::XU_LY_BAO_CAO => 'Xử lý báo cáo vi phạm tài liệu',
            self::AN_TAI_LIEU => 'Tạm ẩn tài liệu',
            self::XOA_MEM_TAI_LIEU => 'Xóa mềm tài liệu',
            self::KHOI_PHUC_TAI_LIEU => 'Khôi phục tài liệu',
            self::XOA_VINH_VIEN_TAI_LIEU => 'Xóa vĩnh viễn tài liệu',
            self::DUYET_GIAI_NGAN_BOUNTY => 'Duyệt giải ngân điểm Bounty',
            self::TU_CHOI_GIAI_NGAN_BOUNTY => 'Từ chối giải ngân điểm Bounty',
            self::XU_LY_BAO_CAO_DANH_GIA => 'Xử lý báo cáo đánh giá môn học',
            self::AN_DANH_GIA => 'Tạm ẩn đánh giá môn học',
            self::XOA_DANH_GIA => 'Xóa đánh giá môn học',
            self::THAY_DOI_KHOA => 'Thay đổi danh mục Khoa',
            self::THAY_DOI_CHUYEN_NGANH => 'Thay đổi Chuyên ngành',
            self::THAY_DOI_MON_HOC => 'Thay đổi Môn học',
            self::THAY_DOI_CAU_HINH => 'Thay đổi cấu hình hệ thống',
            self::DON_DEP_THUNG_RAC => 'Thanh lọc thùng rác hết hạn',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DANG_NHAP_ADMIN, self::DANG_XUAT_ADMIN => 'blue',
            self::DUYET_KYC, self::MO_KHOA_TAI_KHOAN, self::KHOI_PHUC_TAI_LIEU, self::DUYET_GIAI_NGAN_BOUNTY => 'emerald',
            self::TU_CHOI_KYC, self::KHOA_TAI_KHOAN, self::XOA_MEM_TAI_LIEU, self::XOA_VINH_VIEN_TAI_LIEU, self::XOA_SINH_VIEN_RAW, self::XOA_DANH_GIA, self::TU_CHOI_GIAI_NGAN_BOUNTY => 'rose',
            self::AN_TAI_LIEU, self::GHI_NHAN_VI_PHAM, self::XU_LY_BAO_CAO, self::XU_LY_BAO_CAO_DANH_GIA, self::AN_DANH_GIA => 'amber',
            self::NAP_SINH_VIEN_RAW, self::CAP_NHAT_SINH_VIEN_RAW => 'indigo',
            self::THAY_DOI_VAI_TRO, self::THAY_DOI_CAU_HINH => 'purple',
            self::THAY_DOI_KHOA, self::THAY_DOI_CHUYEN_NGANH, self::THAY_DOI_MON_HOC => 'teal',
            self::DON_DEP_THUNG_RAC => 'slate',
        };
    }
}
