<?php

namespace App\Services\Auth\Actions;

use App\Models\NguoiDung;
use App\Services\Auth\Repositories\IdentityRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Action đăng xuất và xóa tài khoản người dùng
class DeleteUserAccountAction
{
    public function __construct(
        protected IdentityRepository $identityRepository
    ) {}

    public function execute(Request $request, NguoiDung $user): bool
    {
        Auth::logout();

        $deleted = $this->identityRepository->deleteAccount($user);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $deleted;
    }
}

