<?php

namespace App\Modules\Identity\Application\Actions;

use App\Models\NguoiDung;
use App\Modules\Identity\Infrastructure\Persistence\IdentityRepository;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;

// Action đăng ký tài khoản sinh viên mới
class RegisterStudentAction
{
    public function __construct(
        protected IdentityRepository $identityRepository
    ) {}

    public function execute(array $data): NguoiDung
    {
        $user = $this->identityRepository->registerStudent($data);

        event(new Registered($user));

        Auth::login($user);

        return $user;
    }
}

