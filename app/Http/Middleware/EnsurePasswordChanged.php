<?php

namespace App\Http\Middleware;

use App\Enums\TrangThaiNguoiDung;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware chặn các sinh viên đang học chưa đổi mật khẩu lần đầu.
 *
 * Luồng:
 *   - Sau khi sinh viên đăng nhập thành công với mật khẩu mặc định,
 *     tài khoản có trang_thai = CHUA_XAC_THUC.
 *   - Middleware này redirect về trang đổi mật khẩu bắt buộc cho đến khi
 *     trang_thai chuyển sang DANG_HOAT_DONG.
 *   - Cựu sinh viên (CUU_SINH_VIEN) và các vai trò khác không bị ảnh hưởng.
 */
class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return $next($request);
        }

        // Chỉ áp dụng cho sinh viên đang học ở trạng thái chưa xác thực (chưa đổi MK)
        if (
            $user->trang_thai === TrangThaiNguoiDung::CHUA_XAC_THUC &&
            $user->vai_tro->value === 'SINH_VIEN'
        ) {
            // Cho phép truy cập route đổi mật khẩu và logout
            if ($request->routeIs('password.change', 'password.change.update', 'logout', 'auth.logout')) {
                return $next($request);
            }

            return redirect()->route('password.change')
                ->with('warning', 'Bạn phải đổi mật khẩu trước khi tiếp tục sử dụng hệ thống.');
        }

        return $next($request);
    }
}
