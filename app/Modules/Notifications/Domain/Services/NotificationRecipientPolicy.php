<?php

namespace App\Modules\Notifications\Domain\Services;

use App\Enums\TrangThaiNguoiDung;
use App\Enums\VaiTroNguoiDung;
use App\Models\NguoiDung;
use Illuminate\Database\Eloquent\Collection;

class NotificationRecipientPolicy
{
    // Lấy danh sách Kiểm duyệt viên hợp lệ để nhận thông báo kiểm duyệt tài liệu (UC-MOD-01, UC-MOD-06)
    public function getModeratorsForDocumentReview(): Collection
    {
        return NguoiDung::query()
            ->where('trang_thai', TrangThaiNguoiDung::DANG_HOAT_DONG)
            ->where(function ($q) {
                $q->where('vai_tro', VaiTroNguoiDung::KIEM_DUYET_VIEN)
                  ->orWhere('vai_tro', VaiTroNguoiDung::QUAN_TRI_VIEN);
            })
            ->get();
    }

    // Lấy danh sách Quản trị viên xử lý hồ sơ KYC cựu sinh viên (UC-MOD-07, UC-ADM-01)
    public function getAdminsForKyc(): Collection
    {
        return NguoiDung::query()
            ->where('trang_thai', TrangThaiNguoiDung::DANG_HOAT_DONG)
            ->where('vai_tro', VaiTroNguoiDung::QUAN_TRI_VIEN)
            ->get();
    }

    // Lấy danh sách Kiểm duyệt viên xử lý báo cáo vi phạm bài đánh giá (UC-MOD-03)
    public function getModeratorsForReviewReports(): Collection
    {
        return NguoiDung::query()
            ->where('trang_thai', TrangThaiNguoiDung::DANG_HOAT_DONG)
            ->whereIn('vai_tro', [VaiTroNguoiDung::KIEM_DUYET_VIEN, VaiTroNguoiDung::QUAN_TRI_VIEN])
            ->get();
    }

    // Kiểm tra xem người dùng có quyền nhận thông báo quản trị cấp cao không
    public function canReceiveAdminNotification(NguoiDung $user): bool
    {
        return $user->laQuanTriVien() && $user->dangHoatDong();
    }

    // Kiểm tra xem người dùng có quyền nhận thông báo kiểm duyệt không
    public function canReceiveModeratorNotification(NguoiDung $user): bool
    {
        return ($user->laKiemDuyetVien() || $user->laQuanTriVien()) && $user->dangHoatDong();
    }
}

