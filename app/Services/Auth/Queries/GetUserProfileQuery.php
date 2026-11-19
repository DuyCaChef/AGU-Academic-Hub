<?php

namespace App\Services\Auth\Queries;

use App\Models\NguoiDung;
use Illuminate\Contracts\Auth\MustVerifyEmail;

// Query lấy dữ liệu hiển thị biểu mẫu hồ sơ người dùng
class GetUserProfileQuery
{
    public function execute(?NguoiDung $user): array
    {
        return [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status'          => session('status'),
        ];
    }
}

