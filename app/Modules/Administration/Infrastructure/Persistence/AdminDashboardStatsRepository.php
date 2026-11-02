<?php

namespace App\Modules\Administration\Infrastructure\Persistence;

use App\Models\BaoCaoViPham;
use App\Models\NguoiDung;
use App\Models\TaiLieu;
use App\Models\YeuCauGoBoTaiLieu;
use App\Models\YeuCauXacThuc;

class AdminDashboardStatsRepository
{
    // Đếm số lượng hồ sơ KYC đang chờ duyệt
    public function getPendingKycCount(): int
    {
        return YeuCauXacThuc::query()
            ->where('trang_thai', 'CHO_DUYET')
            ->count();
    }

    // Đếm số lượng báo cáo vi phạm đang chờ xử lý
    public function getPendingReportsCount(): int
    {
        return BaoCaoViPham::query()
            ->where('trang_thai_xu_ly', 'CHO_XU_LY')
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

    // Đếm tổng số lượng người dùng trong hệ thống
    public function getTotalUsersCount(): int
    {
        return NguoiDung::query()->count();
    }

    // Đếm tổng số lượng tài liệu trong hệ thống
    public function getTotalDocumentsCount(): int
    {
        return TaiLieu::query()->count();
    }
}

