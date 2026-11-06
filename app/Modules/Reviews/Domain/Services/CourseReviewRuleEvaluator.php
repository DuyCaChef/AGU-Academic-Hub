<?php

namespace App\Modules\Reviews\Domain\Services;

use App\Enums\TrangThaiDanhGiaMonHoc;
use App\Enums\VaiTroNguoiDung;
use App\Models\DanhGiaMonHoc;
use App\Models\NguoiDung;

// Evaluator đánh giá các quy tắc nghiệp vụ và phân quyền liên quan đến đánh giá môn học
class CourseReviewRuleEvaluator
{
    // Kiểm tra quyền chỉnh sửa đánh giá (chỉ chính tác giả)
    public function canEdit(?NguoiDung $user, DanhGiaMonHoc $review): bool
    {
        if (!$user) {
            return false;
        }

        $userId = $user->ma_nguoi_dung ?? $user->getKey();
        return ($review->ma_nguoi_dung === $userId || $review->ma_nguoi_danh_gia === $userId);
    }

    // Kiểm tra quyền xóa đánh giá (tác giả hoặc Quản trị viên/Kiểm duyệt viên)
    public function canDelete(?NguoiDung $user, DanhGiaMonHoc $review): bool
    {
        if (!$user) {
            return false;
        }

        $userId = $user->ma_nguoi_dung ?? $user->getKey();
        $isAuthor = ($review->ma_nguoi_dung === $userId || $review->ma_nguoi_danh_gia === $userId);
        
        $isAdminOrMod = (method_exists($user, 'laQuanTriVien') && $user->laQuanTriVien())
            || (method_exists($user, 'laKiemDuyetVien') && $user->laKiemDuyetVien())
            || in_array($user->vai_tro, [VaiTroNguoiDung::QUAN_TRI_VIEN, VaiTroNguoiDung::KIEM_DUYET_VIEN]);

        return $isAuthor || $isAdminOrMod;
    }

    // Kiểm tra quyền bình chọn (không được tự vote bài của chính mình, bài phải hợp lệ)
    public function canVoteHelpful(?NguoiDung $user, DanhGiaMonHoc $review): bool
    {
        return $this->canVote($user, $review);
    }

    public function canVote(?NguoiDung $user, DanhGiaMonHoc $review): bool
    {
        if (!$user) {
            return false;
        }

        // Không cho phép bình chọn nếu bài bị xóa mềm hoặc không ở trạng thái ĐÃ DUYỆT
        if ($review->trashed() || ($review->trang_thai !== TrangThaiDanhGiaMonHoc::DA_DUYET && $review->trang_thai !== 'DA_DUYET')) {
            return false;
        }

        $userId = $user->ma_nguoi_dung ?? $user->getKey();
        return ($review->ma_nguoi_dung !== $userId && $review->ma_nguoi_danh_gia !== $userId);
    }
}
