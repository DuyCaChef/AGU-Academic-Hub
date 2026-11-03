<?php

namespace App\Modules\Identity\Domain\Services;

use App\Enums\LoaiXacThuc;
use App\Enums\TrangThaiNguoiDung;
use App\Enums\TrangThaiXacThuc;
use App\Enums\VaiTroNguoiDung;
use App\Models\NguoiDung;

// Evaluator thẩm định các quy tắc nghiệp vụ tài khoản, email trường và trạng thái đăng nhập
class IdentityRuleEvaluator
{
    // Kiểm tra định dạng email sinh viên/giảng viên thuộc tên miền AGU
    public function isUniversityEmail(string $email): bool
    {
        $val = strtolower(trim($email));
        return str_ends_with($val, '@student.agu.edu.vn') || str_ends_with($val, '@agu.edu.vn');
    }

    // Đánh giá điều kiện cho phép người dùng đăng nhập vào hệ thống
    public function canLogin(NguoiDung $user): array
    {
        // 1. Kiểm tra tài khoản bị tạm khóa
        if ($user->trang_thai === TrangThaiNguoiDung::TAM_KHOA) {
            return [
                'allowed' => false,
                'message' => 'Tài khoản của bạn hiện đang bị tạm khóa. Vui lòng liên hệ ban quản trị để được hỗ trợ.',
            ];
        }

        // 2. Kiểm tra tài khoản cựu sinh viên đang chờ duyệt hồ sơ KYC
        if (
            $user->vai_tro === VaiTroNguoiDung::CUU_SINH_VIEN &&
            $user->trang_thai === TrangThaiNguoiDung::CHUA_XAC_THUC
        ) {
            $rejectedKyc = $user->yeuCauXacThucs()
                ->where('loai_xac_thuc', LoaiXacThuc::CUU_SINH_VIEN)
                ->where('trang_thai', TrangThaiXacThuc::TU_CHOI)
                ->latest('thoi_gian_duyet')
                ->first();

            if ($rejectedKyc) {
                return [
                    'allowed' => false,
                    'is_rejected' => true,
                    'message' => 'Hồ sơ cựu sinh viên của bạn đã bị từ chối: ' . $rejectedKyc->ly_do_tu_choi,
                ];
            }

            return [
                'allowed' => false,
                'is_pending' => true,
                'message' => 'Hồ sơ cựu sinh viên của bạn đang chờ xử lý. Bạn sẽ được phép đăng nhập sau khi hồ sơ được duyệt.',
            ];
        }

        return ['allowed' => true];
    }

    // Kiểm tra có phải lần đầu đăng nhập của sinh viên (cần chuyển hướng đổi mật khẩu)
    public function isFirstTimeLogin(?NguoiDung $user): bool
    {
        if (!$user) {
            return false;
        }

        return $user->vai_tro === VaiTroNguoiDung::SINH_VIEN &&
            $user->trang_thai === TrangThaiNguoiDung::CHUA_XAC_THUC;
    }
}

