<?php

namespace App\Modules\Documents\Domain\Services;

use App\Models\TaiLieu;

class DocumentDeduplicationEvaluator
{
    // Kiểm tra trùng lặp nhị phân 100% bằng chuỗi băm SHA-256 (Lọc trùng Lớp 1)
    public function findExactDuplicateBySha256(int $maMonHoc, string $sha256Hash): ?TaiLieu
    {
        if (empty($sha256Hash)) {
            return null;
        }

        return TaiLieu::query()
            ->where('ma_mon_hoc', $maMonHoc)
            ->where('ma_bam_file', $sha256Hash)
            ->first();
    }

    // Tính toán chuỗi băm SimHash 64-bit từ văn bản trích xuất (Lọc trùng Lớp 2)
    public function calculateSimHash(string $text): string
    {
        $words = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY);
        if (empty($words)) {
            return str_repeat('0', 64);
        }

        $v = array_fill(0, 64, 0);

        foreach ($words as $word) {
            $hash = hash('fnv1a64', $word);
            $binary = sprintf('%064b', hexdec(substr($hash, 0, 16)));

            for ($i = 0; $i < 64; $i++) {
                $v[$i] += ($binary[$i] === '1') ? 1 : -1;
            }
        }

        $simHash = '';
        for ($i = 0; $i < 64; $i++) {
            $simHash .= ($v[$i] > 0) ? '1' : '0';
        }

        return $simHash;
    }

    // Đo lường tỷ lệ tương đồng nội dung giữa hai mã băm SimHash (Cosine / Hamming distance)
    public function calculateSimilarity(string $simHashA, string $simHashB): float
    {
        if (strlen($simHashA) !== 64 || strlen($simHashB) !== 64) {
            return 0.0;
        }

        $matchingBits = 0;
        for ($i = 0; $i < 64; $i++) {
            if ($simHashA[$i] === $simHashB[$i]) {
                $matchingBits++;
            }
        }

        return round($matchingBits / 64, 4);
    }
}

