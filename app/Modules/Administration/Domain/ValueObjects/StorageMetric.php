<?php

namespace App\Modules\Administration\Domain\ValueObjects;

class StorageMetric
{
    // Định dạng dung lượng từ KB sang chuỗi thân thiện với người dùng (B, KB, MB, GB)
    public static function formatKb(int|float $kb, int $decimals = 2): string
    {
        if ($kb <= 0) {
            return '0 KB';
        }

        if ($kb < 1024) {
            return number_format($kb, $decimals) . ' KB';
        }

        $mb = $kb / 1024;
        if ($mb < 1024) {
            return number_format($mb, $decimals) . ' MB';
        }

        $gb = $mb / 1024;
        return number_format($gb, $decimals) . ' GB';
    }

    // Quy đổi từ bytes sang kilobytes (KB)
    public static function bytesToKb(int|float $bytes): float
    {
        return round($bytes / 1024, 2);
    }

    // Quy đổi từ kilobytes (KB) sang megabytes (MB)
    public static function kbToMb(int|float $kb): float
    {
        return round($kb / 1024, 2);
    }

    // Quy đổi từ megabytes (MB) sang kilobytes (KB)
    public static function mbToKb(int|float $mb): float
    {
        return round($mb * 1024, 2);
    }
}
