<?php

namespace App\Http\Controllers\Auth;

use App\Enums\TrangThaiNguoiDung;
use App\Enums\VaiTroNguoiDung;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
            'kycStatus' => session('kyc_status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->xacThucDangNhap();

        $request->session()->regenerate();

        $user = Auth::user();
        if (
            $user &&
            $user->vai_tro === VaiTroNguoiDung::SINH_VIEN &&
            $user->trang_thai === TrangThaiNguoiDung::CHUA_XAC_THUC
        ) {
            return redirect()->route('password.change')
                ->with('warning', 'Đây là lần đầu đăng nhập. Bạn bắt buộc phải đổi mật khẩu để kích hoạt tài khoản.');
        }

        $defaultRoute = $user?->laQuanTriVien() || $user?->laKiemDuyetVien()
            ? 'admin.dashboard'
            : 'dashboard';

        return redirect()->intended(route($defaultRoute, absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function dangXuat(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('auth.login');
    }
}
