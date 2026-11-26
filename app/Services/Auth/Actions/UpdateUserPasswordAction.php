<?php

namespace App\Services\Auth\Actions;

use App\Models\NguoiDung;
use App\Services\Auth\Repositories\IdentityRepository;

// Action cập nhật mật khẩu mới cho người dùng
class UpdateUserPasswordAction
{
    public function __construct(
        protected IdentityRepository $identityRepository
    ) {}

    public function execute(NguoiDung $user, string $newPassword): bool
    {
        return $this->identityRepository->updatePassword($user, $newPassword);
    }
}

