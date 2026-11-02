<?php

namespace App\Modules\Identity\Application\Actions;

use App\Models\NguoiDung;
use App\Modules\Identity\Infrastructure\Persistence\IdentityRepository;

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

