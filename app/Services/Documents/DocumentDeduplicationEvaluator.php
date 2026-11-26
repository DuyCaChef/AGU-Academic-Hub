<?php

namespace App\Services\Documents;

use App\Models\TaiLieu;
use App\Services\Documents\Extraction\DocumentTextExtractor;

class DocumentDeduplicationEvaluator
{
    public function __construct(
        protected ?DocumentTextExtractor $textExtractor = null
    ) {
        $this->textExtractor = $textExtractor ?? new DocumentTextExtractor();
    }

    // 1. Kiểm tra trùng lặp nhị phân 100% bằng chuỗi băm SHA-256 (Lọc trùng Lớp 1)
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

    // 2. Tính toán chuỗi băm SimHash 64-bit từ văn bản trích xuất (Lọc trùng Lớp 2)
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

    // 3. Đo lường tỷ lệ tương đồng nội dung giữa hai mã băm SimHash (Bit agreement percentage)
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

    // 4. Tìm kiếm các tài liệu có độ tương đồng vượt ngưỡng (strictly > 85%) trong cùng môn học
    public function findSimilarDocuments(int $maMonHoc, string $simHash, float $threshold = 0.85, ?int $excludeDocId = null): array
    {
        if (empty($simHash) || $simHash === str_repeat('0', 64)) {
            return [];
        }

        $existingDocs = TaiLieu::query()
            ->where('ma_mon_hoc', $maMonHoc)
            ->whereNotNull('chu_ky_noi_dung')
            ->when($excludeDocId !== null, fn($q) => $q->where('ma_tai_lieu', '!=', $excludeDocId))
            ->get();

        $matches = [];
        foreach ($existingDocs as $doc) {
            $sim = $this->calculateSimilarity($simHash, $doc->chu_ky_noi_dung);
            // Nghiệp vụ yêu cầu: Ngưỡng TRÊN 85% (strictly > 0.85, không nhầm với >= 0.85)
            if ($sim > $threshold) {
                $matches[] = [
                    'document'   => $doc,
                    'similarity' => $sim,
                ];
            }
        }

        // Sắp xếp giảm dần theo độ tương đồng cao nhất
        usort($matches, fn($a, $b) => $b['similarity'] <=> $a['similarity']);

        return $matches;
    }

    // 5. Thẩm định tương đồng tổng hợp cho một tệp tin vật lý
    public function evaluateFileSimilarity(int $maMonHoc, string $filePath, string $extension, ?int $excludeDocId = null): array
    {
        $text = $this->textExtractor->extractText($filePath, $extension, skipCover: true);
        $simHash = $this->calculateSimHash($text);

        $similarDocs = $this->findSimilarDocuments($maMonHoc, $simHash, threshold: 0.85, excludeDocId: $excludeDocId);

        $highestSim = !empty($similarDocs) ? $similarDocs[0]['similarity'] : 0.0;
        $bestMatch = !empty($similarDocs) ? $similarDocs[0]['document'] : null;

        return [
            'sim_hash'            => $simHash,
            'extracted_length'    => mb_strlen($text),
            'has_high_similarity' => !empty($similarDocs),
            'highest_similarity'  => $highestSim,
            'matched_document'    => $bestMatch,
            'similar_documents'   => $similarDocs,
        ];
    }
}
