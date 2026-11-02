<?php

namespace App\Modules\Identity\Infrastructure\Storage;

use App\Modules\Identity\Domain\ValueObjects\KycProofFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class PrivateKycStorage
{
    // Lưu trữ file minh chứng vào khu vực private storage
    public function store(UploadedFile $file, string $safeFileName): string
    {
        $storedPath = $file->storeAs(KycProofFile::PRIVATE_STORAGE_DIR, $safeFileName, 'local');

        if (!$storedPath) {
            throw new RuntimeException('Không thể lưu trữ tệp minh chứng vào private storage.');
        }

        return $storedPath;
    }

    // Kiểm tra tệp minh chứng có tồn tại trên disk private không
    public function exists(string $path): bool
    {
        return Storage::disk('local')->exists($path);
    }

    // Lấy đường dẫn vật lý đầy đủ của tệp minh chứng
    public function fullPath(string $path): string
    {
        return Storage::disk('local')->path($path);
    }

    // Trả về response stream tệp minh chứng an toàn
    public function response(string $path, array $headers = []): \Symfony\Component\HttpFoundation\Response
    {
        return Storage::disk('local')->response($path, null, $headers);
    }

    // Xóa tệp minh chứng khỏi private storage
    public function delete(string $path): bool
    {
        return Storage::disk('local')->delete($path);
    }
}
