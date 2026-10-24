<?php

namespace App\Http\Controllers\Auth;

use App\Enums\TrangThaiNguoiDung;
use App\Enums\VaiTroNguoiDung;
use App\Http\Controllers\Controller;
use App\Models\DanhSachSinhVienRaw;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class FirstTimePasswordController extends Controller
{
    /**
     * Hiển thị giao diện bắt buộc đổi mật khẩu lần đầu cho sinh viên.
     */
    public function hienThiFormDoiMatKhau(): Response|RedirectResponse
    {
        $user = Auth::user();

        // Nếu người dùng đã kích hoạt rồi thì chuyển thẳng vào Dashboard
        if ($user->trang_thai === TrangThaiNguoiDung::DANG_HOAT_DONG) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Auth/FirstTimeChangePassword', [
            'user' => [
                'ho_ten' => $user->ho_ten,
                'mssv'   => $user->mssv,
                'email'  => $user->email,
            ],
        ]);
    }

    /**
     * Cập nhật mật khẩu mới và kích hoạt tài khoản sinh viên.
     */
    public function capNhatMatKhau(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'password.required'  => 'Vui lòng nhập mật khẩu mới.',
            'password.confirmed' => 'Mật khẩu xác nhận không trùng khớp.',
        ]);

        $user = $request->user();

        // 1. Cập nhật mật khẩu mới và chuyển trạng thái sang ĐANG_HOAT_DONG
        $user->update([
            'mat_khau'   => $request->password, // model NguoiDung tự động hash qua casts
            'trang_thai' => TrangThaiNguoiDung::DANG_HOAT_DONG,
        ]);

        // 2. Đánh dấu bản ghi trong bảng dữ liệu thô sinh viên đã kích hoạt
        $rawStudent = DanhSachSinhVienRaw::where('mssv', $user->mssv)->first();
        if ($rawStudent) {
            $rawStudent->danhDauKichHoat();
        }

        return redirect()->route('dashboard')->with(
            'success',
            'Chúc mừng bạn đã kích hoạt tài khoản thành công! Mật khẩu mới đã được lưu.'
        );
    }
}
