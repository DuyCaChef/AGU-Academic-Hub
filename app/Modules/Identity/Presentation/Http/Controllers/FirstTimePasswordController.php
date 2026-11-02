<?php

namespace App\Modules\Identity\Presentation\Http\Controllers;

use App\Enums\TrangThaiNguoiDung;
use App\Http\Controllers\Controller;
use App\Modules\Identity\Application\Actions\ChangeFirstTimePasswordAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class FirstTimePasswordController extends Controller
{
    // Hiển thị giao diện bắt buộc đổi mật khẩu lần đầu cho sinh viên
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

    // Cập nhật mật khẩu mới và kích hoạt tài khoản sinh viên
    public function capNhatMatKhau(Request $request, ChangeFirstTimePasswordAction $action): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'password.required'  => 'Vui lòng nhập mật khẩu mới.',
            'password.confirmed' => 'Mật khẩu xác nhận không trùng khớp.',
        ]);

        $action->execute($request->user(), $request->password);

        return redirect()->route('dashboard')->with(
            'success',
            'Chúc mừng bạn đã kích hoạt tài khoản thành công! Mật khẩu mới đã được lưu.'
        );
    }
}

