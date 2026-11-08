<?php

namespace App\Modules\Administration\Infrastructure\Persistence;

use App\Enums\LoaiXacThuc;
use App\Enums\TrangThaiNguoiDung;
use App\Enums\TrangThaiXacThuc;
use App\Enums\VaiTroNguoiDung;
use App\Models\BaoCaoDanhGia;
use App\Models\BaoCaoViPham;
use App\Models\ChuyenNganh;
use App\Models\DanhSachSinhVienRaw;
use App\Models\Khoa;
use App\Models\MonHoc;
use App\Models\NguoiDung;
use App\Models\TaiLieu;
use App\Enums\TrangThaiYeuCauTaiLieu;
use App\Models\YeuCauGoBoTaiLieu;
use App\Models\YeuCauTaiLieu;
use App\Models\YeuCauXacThuc;

class AdminDashboardStatsRepository
{
    // Đếm tổng số lượng hồ sơ KYC đang chờ duyệt
    public function getPendingKycCount(): int
    {
        return YeuCauXacThuc::query()
            ->where('trang_thai', TrangThaiXacThuc::CHO_DUYET->value)
            ->count();
    }

    // Đếm số lượng hồ sơ KYC cựu sinh viên đang chờ duyệt
    public function getPendingAlumniKycCount(): int
    {
        return YeuCauXacThuc::query()
            ->where('trang_thai', TrangThaiXacThuc::CHO_DUYET->value)
            ->where('loai_xac_thuc', LoaiXacThuc::CUU_SINH_VIEN->value)
            ->count();
    }

    // Thống kê chi tiết tình trạng hồ sơ KYC (chờ duyệt, đã duyệt, từ chối)
    public function getKycOverviewStats(): array
    {
        $pending = $this->getPendingKycCount();
        $pendingAlumni = $this->getPendingAlumniKycCount();
        $approved = YeuCauXacThuc::query()->where('trang_thai', TrangThaiXacThuc::DA_DUYET->value)->count();
        $rejected = YeuCauXacThuc::query()->where('trang_thai', TrangThaiXacThuc::TU_CHOI->value)->count();
        $total = $pending + $approved + $rejected;
        $approvalRate = $total > 0 ? round(($approved / $total) * 100, 1) : 0.0;

        return [
            'total' => $total,
            'pending' => $pending,
            'pendingAlumni' => $pendingAlumni,
            'pendingStudents' => max(0, $pending - $pendingAlumni),
            'approved' => $approved,
            'rejected' => $rejected,
            'approvalRate' => $approvalRate,
        ];
    }

    // Đếm số lượng báo cáo vi phạm đang chờ xử lý
    public function getPendingReportsCount(): int
    {
        return BaoCaoViPham::query()
            ->where('trang_thai_xu_ly', 'CHO_XU_LY')
            ->count();
    }

    // Đếm số lượng báo cáo đánh giá môn học đang chờ xử lý
    public function getPendingReviewReportsCount(): int
    {
        return BaoCaoDanhGia::query()
            ->where('trang_thai', 'CHO_XU_LY')
            ->count();
    }

    // Đếm số lượng tài liệu đang cần duyệt hoặc tạm ẩn kiểm tra
    public function getDocumentsToReviewCount(): int
    {
        return TaiLieu::query()
            ->whereIn('trang_thai', ['CHO_KIEM_TRA', 'TAM_AN_KIEM_TRA'])
            ->count();
    }

    // Đếm số lượng yêu cầu gỡ bỏ tài liệu đang chờ giải quyết
    public function getPendingRemovalRequestsCount(): int
    {
        return YeuCauGoBoTaiLieu::query()
            ->where('trang_thai', 'CHO_XU_LY')
            ->count();
    }

    // Đếm số lượng tài liệu trong thùng rác
    public function getTrashedDocumentsCount(): int
    {
        return TaiLieu::onlyTrashed()->count();
    }

    // Đếm số lượng tài liệu trong thùng rác đã quá hạn lưu trữ 60 ngày
    public function getExpiredTrashedDocumentsCount(): int
    {
        return TaiLieu::onlyTrashed()
            ->where('han_luu_thung_rac', '<=', now())
            ->count();
    }

    // Đếm tổng số lượng người dùng trong hệ thống
    public function getTotalUsersCount(): int
    {
        return NguoiDung::query()->count();
    }

    // Thống kê trạng thái tài khoản người dùng và vai trò
    public function getUserStatusStats(): array
    {
        $active = NguoiDung::query()->where('trang_thai', TrangThaiNguoiDung::DANG_HOAT_DONG->value)->count();
        $locked = NguoiDung::query()->where('trang_thai', TrangThaiNguoiDung::TAM_KHOA->value)->count();
        $unverified = NguoiDung::query()->where('trang_thai', TrangThaiNguoiDung::CHUA_XAC_THUC->value)->count();
        $total = NguoiDung::query()->count();

        return [
            'total' => $total,
            'active' => $active,
            'locked' => $locked,
            'unverified' => $unverified,
            'byRole' => [
                'students' => NguoiDung::query()->where('vai_tro', VaiTroNguoiDung::SINH_VIEN->value)->count(),
                'alumni' => NguoiDung::query()->where('vai_tro', VaiTroNguoiDung::CUU_SINH_VIEN->value)->count(),
                'moderators' => NguoiDung::query()->where('vai_tro', VaiTroNguoiDung::KIEM_DUYET_VIEN->value)->count(),
                'admins' => NguoiDung::query()->where('vai_tro', VaiTroNguoiDung::QUAN_TRI_VIEN->value)->count(),
            ],
            'highViolations' => NguoiDung::query()->where('so_lan_vi_pham', '>=', 3)->count(),
        ];
    }

    // Thống kê dữ liệu thô sinh viên nạp trước
    public function getRawStudentStats(): array
    {
        $total = DanhSachSinhVienRaw::query()->count();
        $activated = DanhSachSinhVienRaw::query()->where('da_kich_hoat', true)->count();
        $unactivated = DanhSachSinhVienRaw::query()->where('da_kich_hoat', false)->count();
        $activationRate = $total > 0 ? round(($activated / $total) * 100, 1) : 0.0;

        return [
            'total' => $total,
            'activated' => $activated,
            'unactivated' => $unactivated,
            'activationRate' => $activationRate,
        ];
    }

    // Thống kê danh mục học thuật: Khoa, Chuyên ngành, Môn học
    public function getAcademicCatalogStats(): array
    {
        return [
            'faculties' => Khoa::query()->count(),
            'majors' => ChuyenNganh::query()->count(),
            'courses' => MonHoc::query()->count(),
        ];
    }

    // Đếm tổng số lượng tài liệu trong hệ thống (không tính thùng rác)
    public function getTotalDocumentsCount(): int
    {
        return TaiLieu::query()->count();
    }

    // Tổng số lượt tải tài liệu toàn hệ thống (tính cả tài liệu đã vào thùng rác)
    public function getTotalDocumentDownloads(): int
    {
        return (int) TaiLieu::withTrashed()->sum('luot_tai');
    }

    // Thống kê tổng quan tài liệu và lượt tải
    public function getDocumentOverviewStats(): array
    {
        $total = $this->getTotalDocumentsCount();
        $trashed = $this->getTrashedDocumentsCount();
        $downloads = $this->getTotalDocumentDownloads();

        return [
            'total' => $total,
            'active' => TaiLieu::query()->whereIn('trang_thai', ['DA_DUYET', 'CONG_KHAI'])->count(),
            'totalDownloads' => $downloads,
            'trashed' => $trashed,
        ];
    }

    // Đếm số lượng yêu cầu tài liệu Bounty đang chờ Admin/Mod duyệt giải ngân
    public function getPendingBountyPayoutsCount(): int
    {
        return YeuCauTaiLieu::query()
            ->where('trang_thai', TrangThaiYeuCauTaiLieu::CHO_GIAI_NGAN->value)
            ->count();
    }
}
