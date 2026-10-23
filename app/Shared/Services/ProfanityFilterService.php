<?php 

namespace App\Shared\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProfanityFilterService
{
    // Thời gian cache từ điển từ cấm (1440 phút = 24 giờ)
    private const CACHE_TTL_MINUTES = 1440;
    private const CACHE_KEY = 'system_prohibited_words_patterns';

    // Danh sách từ cấm dự phòng
    private array $defaultWords = [
        'đm', 'đcm', 'dcm', 'vcl', 'vkl', 'clgt', 'địt', 'đụ', 'lồn', 'buồi', 
        'cặc', 'chó chết', 'ngu vcl', 'mẹ kiếp', 'đĩ', 'thằng chó', 'con mẹ', 'cc', 'cl'
    ];

    /**
     * Kiểm tra xem chuỗi văn bản có chứa từ cấm hay không.
     */
    public function hasProfanity(?string $text): bool
    {
        if (empty($text)) {
            return false;
        }

        return count($this->getViolations($text)) > 0;
    }

    /**
     * Trích xuất danh sách các từ ngữ vi phạm xuất hiện trong văn bản.
     *
     * @return array<string>
     */
    public function getViolations(?string $text): array
    {
        if (empty($text)) {
            return [];
        }

        $patterns = $this->getCompiledPatterns();
        $normalizedText = $this->normalizeText($text);
        $violations = [];

        foreach ($patterns as $word => $pattern) {
            if (preg_match($pattern, $normalizedText) || preg_match($pattern, mb_strtolower($text, 'UTF-8'))) {
                $violations[] = $word;
            }
        }

        return array_unique($violations);
    }

    /**
     * Che từ cấm trong chuỗi bằng ký tự thay thế (mặc định: ***).
     */
    public function clean(?string $text, string $replacement = '***'): string
    {
        if (empty($text)) {
            return '';
        }

        $patterns = $this->getCompiledPatterns();
        $cleanedText = $text;

        foreach ($patterns as $pattern) {
            $cleanedText = preg_replace($pattern, $replacement, $cleanedText);
        }

        return $cleanedText;
    }

    /**
     * Xóa cache từ cấm khi Admin cập nhật danh sách.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Biên dịch Regex Pattern từ CSDL hoặc danh sách mặc định.
     */
    private function getCompiledPatterns(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(self::CACHE_TTL_MINUTES), function () {
            $words = [];

            // Linh hoạt kiểm tra tên bảng từ cấm trong CSDL (tu_cam hoặc prohibited_words)
            $tableName = Schema::hasTable('tu_cam') ? 'tu_cam' : (Schema::hasTable('prohibited_words') ? 'prohibited_words' : null);

            if ($tableName) {
                // Ưu tiên đọc cột tu_ngu hoặc word
                $column = Schema::hasColumn($tableName, 'tu_ngu') ? 'tu_ngu' : 'word';
                $dbWords = DB::table($tableName)
                    ->pluck($column)
                    ->map(fn($w) => trim((string)$w))
                    ->filter()
                    ->toArray();
                $words = array_merge($words, $dbWords);
            }

            if (empty($words)) {
                $words = $this->defaultWords;
            }

            $compiled = [];
            foreach (array_unique($words) as $rawWord) {
                $word = mb_strtolower(trim($rawWord), 'UTF-8');
                if ($word === '') {
                    continue;
                }

                // Hỗ trợ bắt từ ngữ bị chèn khoảng trắng/dấu ngắt: đ.m, đ_m, d m
                $chars = preg_split('//u', preg_quote($word, '/'), -1, PREG_SPLIT_NO_EMPTY);
                $escapedPattern = implode('[\\s\\.\\_\\-\\~\\*]*', $chars);

                $compiled[$word] = '/(?<=^|[\\s\\.\\,\\;\\!\\?\\(\\)\\[\\]\\-_\\/\\\\])' . $escapedPattern . '(?=$|[\\s\\.\\,\\;\\!\\?\\(\\)\\[\\]\\-_\\/\\\\])/ui';
            }

            return $compiled;
        });
    }

    /**
     * Chuẩn hóa Leetspeak lách luật sang ký tự thông thường.
     */
    private function normalizeText(string $text): string
    {
        $normalized = mb_strtolower($text, 'UTF-8');

        $leetspeakMap = [
            '0' => 'o',
            '1' => 'i',
            '3' => 'e',
            '4' => 'a',
            '@' => 'a',
            '!' => 'i',
        ];

        return strtr($normalized, $leetspeakMap);
    }
}