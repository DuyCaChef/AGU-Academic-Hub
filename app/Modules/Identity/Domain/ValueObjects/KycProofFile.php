<?php

namespace App\Modules\Identity\Domain\ValueObjects;

use Illuminate\Support\Str;

class KycProofFile
{
    // Thư mục lưu trữ private cho minh chứng KYC
    public const PRIVATE_STORAGE_DIR = 'private/kyc';

    // Tạo tên file băm an toàn thông tin cá nhân (PII-safe)
    public static function generateSafeFileName(int $userId, string $extension): string
    {
        return sprintf(
            'kyc_%d_%s_%s.%s',
            $userId,
            now()->format('YmdHis'),
            Str::random(16),
            $extension
        );
    }
}

