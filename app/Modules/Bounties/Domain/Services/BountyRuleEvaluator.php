<?php

namespace App\Modules\Bounties\Domain\Services;

use App\Enums\TrangThaiYeuCauChinhSua;
use App\Enums\TrangThaiYeuCauTaiLieu;
use App\Models\DapUngYeuCauTaiLieu;
use App\Models\LanNopTaiLieuThayThe;
use App\Models\NguoiDung;
use App\Models\YeuCauChinhSuaDongGop;
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

    // Đánh giá quyền từ chối bài nộp đóng góp
    public function canRejectSubmission(NguoiDung $user, YeuCauTaiLieu $yeuCau, DapUngYeuCauTaiLieu $dapUng): array
    {
        if ($yeuCau->ma_nguoi_tao !== $user->ma_nguoi_dung) {
            return [
                'allowed' => false,
                'message' => 'Bạn không phải người tạo yêu cầu này.',
            ];
        }

        if ($yeuCau->trang_thai !== TrangThaiYeuCauTaiLieu::MO) {
            return [
                'allowed' => false,
                'message' => 'Chỉ có thể từ chối bài đóng góp khi yêu cầu đang mở.',
            ];
        }

        if ($dapUng->ma_yeu_cau !== $yeuCau->ma_yeu_cau) {
            return [
                'allowed' => false,
                'message' => 'Bài đóng góp không thuộc về yêu cầu này.',
            ];
        }

        if ($dapUng->trang_thai !== 'CHO_DUYET') {
            return [
                'allowed' => false,
                'message' => 'Chỉ có thể từ chối bài đóng góp đang ở trạng thái chờ duyệt.',
            ];
        }

        return ['allowed' => true];
    }

    // Đánh giá điều kiện tạo yêu cầu chỉnh sửa sau khi Bounty hoàn thành (Phần 6)
    public function canRequestCorrection(NguoiDung $user, YeuCauTaiLieu $yeuCau): array
    {
        if ($yeuCau->ma_nguoi_tao !== $user->ma_nguoi_dung) {
            return [
                'allowed' => false,
                'message' => 'Chỉ người tạo yêu cầu săn thưởng mới có quyền yêu cầu chỉnh sửa.',
            ];
        }

        if ($yeuCau->trang_thai !== TrangThaiYeuCauTaiLieu::DA_HOAN_THANH) {
            return [
                'allowed' => false,
                'message' => 'Chỉ có thể yêu cầu chỉnh sửa đối với yêu cầu săn thưởng đã hoàn thành.',
            ];
        }

        if (!$yeuCau->ma_dap_ung_duoc_chon && !$yeuCau->ma_tai_lieu_dap_ung) {
            return [
                'allowed' => false,
                'message' => 'Yêu cầu này chưa có tài liệu đóng góp được chấp nhận để chỉnh sửa.',
            ];
        }

        // Kiểm tra xem đã có yêu cầu chỉnh sửa nào đang hoạt động chưa
        $hasActive = YeuCauChinhSuaDongGop::where('ma_yeu_cau', $yeuCau->ma_yeu_cau)
            ->whereIn('trang_thai', [
                TrangThaiYeuCauChinhSua::CHO_XU_LY->value,
                TrangThaiYeuCauChinhSua::DANG_XU_LY->value,
            ])
            ->exists();

        if ($hasActive) {
            return [
                'allowed' => false,
                'message' => 'Đang có một yêu cầu chỉnh sửa đang hoạt động. Vui lòng chờ xử lý xong trước khi tạo yêu cầu mới.',
            ];
        }

        return ['allowed' => true];
    }

    // Đánh giá quyền phản hồi yêu cầu chỉnh sửa của người đóng góp
    public function canRespondToCorrection(NguoiDung $user, YeuCauChinhSuaDongGop $yccs): array
    {
        if ($yccs->ma_nguoi_dong_gop !== $user->ma_nguoi_dung) {
            return [
                'allowed' => false,
                'message' => 'Bạn không phải người đóng góp được yêu cầu chỉnh sửa.',
            ];
        }

        if ($yccs->trang_thai !== TrangThaiYeuCauChinhSua::CHO_XU_LY) {
            return [
                'allowed' => false,
                'message' => 'Yêu cầu chỉnh sửa này không còn ở trạng thái chờ phản hồi.',
            ];
        }

        return ['allowed' => true];
    }

    // Đánh giá quyền nộp tài liệu thay thế của người đóng góp
    public function canSubmitReplacement(NguoiDung $user, YeuCauChinhSuaDongGop $yccs): array
    {
        if ($yccs->ma_nguoi_dong_gop !== $user->ma_nguoi_dung) {
            return [
                'allowed' => false,
                'message' => 'Bạn không có quyền nộp tài liệu thay thế cho yêu cầu này.',
            ];
        }

        if ($yccs->trang_thai !== TrangThaiYeuCauChinhSua::DANG_XU_LY) {
            return [
                'allowed' => false,
                'message' => 'Yêu cầu chỉnh sửa chưa được chấp nhận xử lý hoặc đã kết thúc.',
            ];
        }

        return ['allowed' => true];
    }

    // Đánh giá quyền xác nhận hoàn tất chỉnh sửa của người tạo Bounty
    public function canConfirmCorrectionCompletion(NguoiDung $user, YeuCauChinhSuaDongGop $yccs, LanNopTaiLieuThayThe $submission): array
    {
        if ($yccs->ma_nguoi_yeu_cau !== $user->ma_nguoi_dung) {
            return [
                'allowed' => false,
                'message' => 'Chỉ người tạo yêu cầu mới có quyền nghiệm thu hoàn tất chỉnh sửa.',
            ];
        }

        if ($yccs->trang_thai !== TrangThaiYeuCauChinhSua::DANG_XU_LY) {
            return [
                'allowed' => false,
                'message' => 'Yêu cầu chỉnh sửa này không ở trạng thái đang xử lý.',
            ];
        }

        if ($submission->ma_yeu_cau_chinh_sua !== $yccs->ma_yeu_cau_chinh_sua) {
            return [
                'allowed' => false,
                'message' => 'Phiên bản tài liệu nộp không thuộc yêu cầu chỉnh sửa này.',
            ];
        }

        return ['allowed' => true];
    }
}

