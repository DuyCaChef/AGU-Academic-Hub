<?php

namespace App\Modules\Identity\Application\Actions;

use App\Enums\TrangThaiNguoiDung;
use App\Models\DanhSachSinhVienRaw;
use App\Models\NguoiDung;

class ChangeFirstTimePasswordAction
{
    // Đổi mật khẩu lần đầu và kích hoạt tài khoản sinh viên
    public function execute(NguoiDung $user, string $newPassword): void
    {
        // 1. Cập nhật mật khẩu mới và chuyển trạng thái sang ĐANG_HOAT_DONG
        $user->update([
            'mat_khau'   => $newPassword, // Model NguoiDung tự động hash qua casts
            'trang_thai' => TrangThaiNguoiDung::DANG_HOAT_DONG,
        ]);

        // 2. Đánh dấu bản ghi trong bảng dữ liệu thô sinh viên đã kích hoạt
        $rawStudent = DanhSachSinhVienRaw::where('mssv', $user->mssv)->first();
        if ($rawStudent) {
            $rawStudent->danhDauKichHoat();
        }
    }
}

