<?php

namespace App\Modules\Identity\Application\Actions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Action đăng xuất người dùng và hủy phiên làm việc
class LogoutUserAction
{
    public function execute(Request $request): void
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}

