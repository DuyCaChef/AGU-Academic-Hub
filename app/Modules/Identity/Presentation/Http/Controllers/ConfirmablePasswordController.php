<?php

namespace App\Modules\Identity\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

// Controller điều phối xác nhận mật khẩu cho các tác vụ nhạy cảm
class ConfirmablePasswordController extends Controller
{
    // Hiển thị giao diện xác nhận mật khẩu
    public function show(): Response
    {
        return Inertia::render('Auth/ConfirmPassword');
    }

    // Xác thực mật khẩu và lưu mốc thời gian xác nhận vào phiên
    public function store(Request $request): RedirectResponse
    {
        if (! Auth::guard('web')->validate([
            'email'    => $request->user()->email,
            'password' => $request->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        $request->session()->put('auth.password_confirmed_at', time());

        return redirect()->intended(route('dashboard', absolute: false));
    }
}

