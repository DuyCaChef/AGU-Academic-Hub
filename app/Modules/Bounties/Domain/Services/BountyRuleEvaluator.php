<?php

namespace App\Modules\Bounties\Domain\Services;

use App\Enums\TrangThaiYeuCauTaiLieu;
use App\Models\NguoiDung;
use App\Models\YeuCauTaiLieu;

// Evaluator đánh giá các quy tắc nghiệp vụ và điều kiện phân quyền săn thưởng tài liệu
class BountyRuleEvaluator
{
    // Đánh giá điều kiện gửi tài liệu phản hồi yêu cầu
    public function canFulfill(NguoiDung $user, YeuCauTaiLieu $yeuCau): array
    {
        // Không cho phép nộp nếu yêu cầu không ở trạng thái MO
        if ($yeuCau->trang_thai !== TrangThaiYeuCauTaiLieu::MO) {
            return [
                'allowed' => false,
                'message' => 'Yêu cầu này không còn mở để tiếp nhận tài liệu.',
            ];
        }

        // Không cho phép người tạo tự giải quyết yêu cầu của chính mình để gian lận điểm
        if ($yeuCau->ma_nguoi_tao === $user->ma_nguoi_dung) {
            return [
                'allowed' => false,
                'message' => 'Bạn không thể tự gửi tài liệu để nhận điểm thưởng của chính mình.',
            ];
        }

        return ['allowed' => true];
    }

    // Đánh giá tính hợp lệ và quyền sở hữu của tài liệu khi nộp phản hồi
    public function canFulfillWithDocument(NguoiDung $user, YeuCauTaiLieu $yeuCau, ?\App\Models\TaiLieu $document): array
    {
        if (!$document || $document->trashed()) {
            return [
                'allowed' => false,
                'message' => 'Tài liệu này không tồn tại hoặc đã bị xóa.',
            ];
        }

        // Chỉ cho phép người sở hữu tài liệu nộp
        if ($document->ma_nguoi_dang !== $user->ma_nguoi_dung) {
            return [
                'allowed' => false,
                'message' => 'Bạn chỉ có thể đóng góp tài liệu do chính bạn đăng tải.',
            ];
        }

        // Chỉ tài liệu đã được duyệt mới được dùng để giải quyết yêu cầu
        $isApproved = ($document->trang_thai === 'DA_DUYET' || $document->trang_thai === \App\Enums\TrangThaiTaiLieu::DA_DUYET);
        if (!$isApproved) {
            return [
                'allowed' => false,
                'message' => 'Chỉ có thể đóng góp tài liệu đã được phê duyệt và đang hoạt động.',
            ];
        }

        // Tài liệu phải thuộc cùng môn học với yêu cầu Bounty
        if ($document->ma_mon_hoc !== $yeuCau->ma_mon_hoc) {
            return [
                'allowed' => false,
                'message' => 'Tài liệu đóng góp phải thuộc cùng môn học với yêu cầu tìm kiếm.',
            ];
        }

        return ['allowed' => true];
    }

    // Đánh giá quyền xác nhận giải ngân (Chính chủ hoặc Quản trị viên / Kiểm duyệt viên)
    public function canConfirm(NguoiDung $user, YeuCauTaiLieu $yeuCau): bool
    {
        $isCreator = ($yeuCau->ma_nguoi_tao === $user->ma_nguoi_dung);
        $isAdminOrMod = (method_exists($user, 'laQuanTriVien') && $user->laQuanTriVien())
            || (method_exists($user, 'laKiemDuyetVien') && $user->laKiemDuyetVien());

        return $isCreator || $isAdminOrMod;
    }

    // Đánh giá quyền hủy yêu cầu (Chính chủ hoặc Quản trị viên)
    public function canCancel(NguoiDung $user, YeuCauTaiLieu $yeuCau): bool
    {
        $isCreator = ($yeuCau->ma_nguoi_tao === $user->ma_nguoi_dung);
        $isAdmin = method_exists($user, 'laQuanTriVien') && $user->laQuanTriVien();

        return $isCreator || $isAdmin;
    }
}

