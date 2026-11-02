<?php

namespace App\Modules\Identity\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Application\Actions\AuthenticateUserAction;
use App\Modules\Identity\Application\Actions\LogoutUserAction;
use App\Modules\Identity\Domain\Services\IdentityRuleEvaluator;
use App\Modules\Identity\Presentation\Http\Requests\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

// Controller điều phối phiên đăng nhập và đăng xuất của người dùng
class AuthenticatedSessionController extends Controller
{
    // Hiển thị giao diện đăng nhập
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status'           => session('status'),
            'kycStatus'        => session('kyc_status'),
        ]);
    }

    // Xử lý yêu cầu đăng nhập
    public function store(
        LoginRequest $request,
        AuthenticateUserAction $action,
        IdentityRuleEvaluator $ruleEvaluator
    ): RedirectResponse {
        $user = $action->execute(
            (string) $request->input('email'),
            (string) $request->input('password'),
            $request->boolean('remember'),
            $request->ip() ?? '127.0.0.1'
        );

        $request->session()->regenerate();

        // Chuyển hướng bắt buộc đổi mật khẩu nếu là lần đầu đăng nhập
        if ($ruleEvaluator->isFirstTimeLogin($user)) {
            return redirect()->route('password.change')
                ->with('warning', 'Đây là lần đầu đăng nhập. Bạn bắt buộc phải đổi mật khẩu để kích hoạt tài khoản.');
        }

        $defaultRoute = ($user->laQuanTriVien() || $user->laKiemDuyetVien())
            ? 'admin.dashboard'
            : 'dashboard';

        return redirect()->intended(route($defaultRoute, absolute: false));
    }

    // Hủy phiên làm việc và đăng xuất người dùng
    public function destroy(Request $request, LogoutUserAction $action): RedirectResponse
    {
        $action->execute($request);

        return redirect('/');
    }
}

