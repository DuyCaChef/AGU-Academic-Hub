<?php

namespace App\Services\Auth\Actions;

use App\Models\NguoiDung;
use App\Services\Auth\Repositories\IdentityRepository;

// Action cập nhật thông tin hồ sơ cá nhân
class UpdateUserProfileAction
{
    public function __construct(
        protected IdentityRepository $identityRepository
    ) {}

    public function execute(NguoiDung $user, array $data): bool
    {
        return $this->identityRepository->updateProfile($user, $data);
    }
}

