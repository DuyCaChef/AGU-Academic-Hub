<?php

namespace App\Modules\Administration\Infrastructure\Storage;

use App\Modules\Administration\Domain\ValueObjects\StorageMetric;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PhysicalStorageScanner
{
    // Quét thống kê dung lượng thư mục KYC trên đĩa Private (Zero PII leakage)
    public function scanKycDirectory(string $diskName = 'local', string $dirPath = 'private/kyc'): array
    {
        $disk = Storage::disk($diskName);
        $totalBytes = 0;
        $filesCount = 0;

        try {
            if ($disk->exists($dirPath)) {
                $files = $disk->allFiles($dirPath);
                $filesCount = count($files);
                foreach ($files as $file) {
                    $totalBytes += (int) $disk->size($file);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('PhysicalStorageScanner: Lỗi khi quét thư mục KYC', [
                'error' => $e->getMessage(),
                'dir' => $dirPath,
            ]);
            $totalBytes = 0;
            $filesCount = 0;
        }

        return [
            'files_count' => $filesCount,
            'total_bytes' => $totalBytes,
        ];
    }

    // Lấy thông số ổ đĩa vật lý của máy chủ nếu hệ điều hành hỗ trợ
    public function getPhysicalDiskStats(): ?array
    {
        try {
            $path = storage_path('app');
            if (!file_exists($path)) {
                $path = base_path();
            }

            $freeBytes = @disk_free_space($path);
            $totalBytes = @disk_total_space($path);

            if ($freeBytes !== false && $totalBytes !== false && $totalBytes > 0) {
                $usedBytes = $totalBytes - $freeBytes;
                $freeKb = $freeBytes / 1024;
                $totalKb = $totalBytes / 1024;
                $usedKb = $usedBytes / 1024;

                return [
                    'tong_dung_luong_o_dia_dinh_dang' => StorageMetric::formatKb($totalKb),
                    'con_trong_dinh_dang' => StorageMetric::formatKb($freeKb),
                    'da_dung_dinh_dang' => StorageMetric::formatKb($usedKb),
                    'ty_le_da_dung_phan_tram' => round(($usedBytes / $totalBytes) * 100, 1),
                ];
            }
        } catch (\Throwable $e) {
            // Không khả dụng trên một số môi trường ảo hóa hoặc sandbox
        }

        return null;
    }

    // Xóa các tệp vật lý trên đĩa sau khi Transaction CSDL đã commit hoàn tất
    public function deletePhysicalFiles(array $filePaths, string $diskName = 'local'): void
    {
        $disk = Storage::disk($diskName);

        foreach ($filePaths as $filePath) {
            try {
                if ($filePath && $disk->exists($filePath)) {
                    $disk->delete($filePath);
                }
            } catch (\Throwable $e) {
                Log::warning("PhysicalStorageScanner: Lỗi xóa file vật lý sau khi purge: {$filePath}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
