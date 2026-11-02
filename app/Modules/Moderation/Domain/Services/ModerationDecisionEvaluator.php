<?php

namespace App\Modules\Moderation\Domain\Services;

use App\Models\NguoiDung;
use App\Models\TaiLieu;

class ModerationDecisionEvaluator
{
    // Đánh giá thẩm quyền kiểm duyệt của người dùng
    public function canModerate(?NguoiDung $user): bool
    {
        if (!$user) {
            return false;
        }

        return $user->laQuanTriVien() || $user->laKiemDuyetVien();
    }

    // Đánh giá quyền xóa vĩnh viễn (chỉ Quản trị viên và sau 60 ngày lưu thùng rác)
    public function canPermanentlyDelete(TaiLieu $document, NguoiDung $user): array
    {
        if (!$user->laQuanTriVien()) {
            return ['allowed' => false, 'status' => 403, 'message' => 'Chỉ quản trị viên mới được xóa vĩnh viễn.'];
        }

        $eligibleAt = $document->han_luu_thung_rac
            ?? $document->ngay_xoa_tam?->copy()->addDays(config('documents.trash_admin_delete_after_days', 60));

        if ($eligibleAt?->isFuture() ?? true) {
            return ['allowed' => false, 'status' => 422, 'message' => 'Tài liệu chưa đủ 60 ngày để Admin xóa vĩnh viễn.'];
        }

        return ['allowed' => true, 'status' => 200, 'message' => 'Hợp lệ'];
    }
}

