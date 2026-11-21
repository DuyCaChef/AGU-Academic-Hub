<?php

namespace App\Services\Documents\Extraction;

use ZipArchive;

class DocumentTextExtractor
{
    // Trích xuất văn bản từ tệp tin hỗ trợ (PDF, DOCX, PPTX, TXT) và bỏ qua trang bìa theo đặc tả
    public function extractText(string $filePath, string $extension, bool $skipCover = true): string
    {
        if (!file_exists($filePath) || filesize($filePath) === 0) {
            return '';
        }

        $ext = strtolower($extension);

        try {
            return match ($ext) {
                'txt'  => $this->extractFromTxt($filePath, $skipCover),
                'docx' => $this->extractFromDocx($filePath, $skipCover),
                'pptx' => $this->extractFromPptx($filePath, $skipCover),
                'pdf'  => $this->extractFromPdf($filePath, $skipCover),
                default => '',
            };
        } catch (\Throwable $e) {
            // Đảm bảo không làm sập tiến trình khi gặp tệp hỏng hoặc mã hóa
            report($e);
            return '';
        }
    }

    // 1. Trích xuất từ tệp văn bản thuần (TXT)
    protected function extractFromTxt(string $filePath, bool $skipCover): string
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return '';
        }

        // Loại bỏ UTF-8 BOM nếu có
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        if (!$skipCover) {
            return $content;
        }

        // Nếu tệp có định dạng phân trang (ví dụ dấu ngắt trang hoặc header Trang 1)
        if (preg_match('/(?:^|\n)(?:={3,}|-{3,}|\f|\[Trang 2\]|Page 2)/i', $content, $matches, PREG_OFFSET_CAPTURE)) {
            $offset = $matches[0][1];
            return trim(substr($content, $offset));
        }

        // Bỏ qua đoạn mở đầu (khoảng 300 ký tự đầu tiên nếu tệp đủ dài > 600 ký tự)
        if (mb_strlen($content) > 600) {
            $lines = explode("\n", $content);
            if (count($lines) > 5) {
                // Bỏ 2 dòng đầu (tiêu đề/bìa)
                array_shift($lines);
                array_shift($lines);
                return trim(implode("\n", $lines));
            }
        }

        return $content;
    }

    // 2. Trích xuất từ Microsoft Word (DOCX)
    protected function extractFromDocx(string $filePath, bool $skipCover): string
    {
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            return '';
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if (!$xml) {
            return '';
        }

        // Bóc tách các thẻ văn bản paragraph <w:p>
        preg_match_all('/<w:p[^>]*>(.*?)<\/w:p>/is', $xml, $paragraphs);
        $textParts = [];

        foreach ($paragraphs[1] as $pXml) {
            $cleanText = trim(strip_tags($pXml));
            if (!empty($cleanText)) {
                $textParts[] = $cleanText;
            }
        }

        if (empty($textParts)) {
            return trim(strip_tags($xml));
        }

        // Bỏ qua trang bìa: Nếu có nhiều hơn 3 đoạn văn bản, bỏ 2 đoạn đầu tiên (tiêu đề bìa/tác giả)
        if ($skipCover && count($textParts) > 3) {
            array_shift($textParts);
            array_shift($textParts);
        }

        return implode(' ', $textParts);
    }

    // 3. Trích xuất từ Microsoft PowerPoint (PPTX)
    protected function extractFromPptx(string $filePath, bool $skipCover): string
    {
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            return '';
        }

        $slideTexts = [];
        $slideIndex = 1;

        while (true) {
            $slideXml = $zip->getFromName("ppt/slides/slide{$slideIndex}.xml");
            if ($slideXml === false) {
                break;
            }

            // Theo đặc tả: Bỏ qua trang bìa (slide 1) khi bài giảng có nhiều hơn 1 slide
            if (!($skipCover && $slideIndex === 1)) {
                $text = trim(strip_tags($slideXml));
                if (!empty($text)) {
                    $slideTexts[] = $text;
                }
            }

            $slideIndex++;
        }

        $zip->close();

        // Nếu bài giảng chỉ có đúng 1 slide và skipCover=true khiến rỗng, lấy chính slide 1
        if (empty($slideTexts) && $slideIndex === 2) {
            $zip2 = new ZipArchive();
            if ($zip2->open($filePath) === true) {
                $slide1 = $zip2->getFromName('ppt/slides/slide1.xml');
                $zip2->close();
                if ($slide1) {
                    return trim(strip_tags($slide1));
                }
            }
        }

        return implode(' ', $slideTexts);
    }

    // 4. Trích xuất từ Adobe PDF
    protected function extractFromPdf(string $filePath, bool $skipCover): string
    {
        $raw = file_get_contents($filePath);
        if ($raw === false) {
            return '';
        }

        // Tìm tất cả các stream nội dung trong tệp PDF
        preg_match_all('/stream[\r\n]+(.*?)[\r\n]+endstream/is', $raw, $matches);
        if (empty($matches[1])) {
            return '';
        }

        $pageTexts = [];
        foreach ($matches[1] as $stream) {
            $decompressed = @gzuncompress($stream);
            $data = ($decompressed !== false) ? $decompressed : $stream;

            // Trích xuất các chuỗi text giữa BT (Begin Text) và ET (End Text)
            if (preg_match_all('/BT(.*?)ET/is', $data, $textBlocks)) {
                $blockString = '';
                foreach ($textBlocks[1] as $block) {
                    // Trích xuất chuỗi trong ngoặc đơn (text) Tj hoặc TJ
                    if (preg_match_all('/\((.*?)\)\s*(?:Tj|TJ|\')/is', $block, $strings)) {
                        $blockString .= ' ' . implode(' ', $strings[1]);
                    } elseif (preg_match_all('/\[(.*?)\]\s*TJ/is', $block, $arrays)) {
                        foreach ($arrays[1] as $arr) {
                            if (preg_match_all('/\((.*?)\)/is', $arr, $subStrings)) {
                                $blockString .= ' ' . implode(' ', $subStrings[1]);
                            }
                        }
                    }
                }

                $cleanBlock = trim($blockString);
                if (!empty($cleanBlock)) {
                    $pageTexts[] = $cleanBlock;
                }
            }
        }

        if (empty($pageTexts)) {
            // Fallback trích xuất chuỗi có thể đọc được trong PDF
            if (preg_match_all('/\(([A-Za-z0-9\s\.\,\:\;\-\_\+\*\/\?\!\@\#\$\%\^\&\(\)\{\}\[\]\<\>\p{L}]{3,})\)/u', $raw, $fallbackMatches)) {
                $pageTexts[] = implode(' ', $fallbackMatches[1]);
            }
        }

        // Bỏ qua trang bìa: Nếu có nhiều hơn 1 trang, bỏ trang đầu tiên
        if ($skipCover && count($pageTexts) > 1) {
            array_shift($pageTexts);
        }

        return implode(' ', $pageTexts);
    }
}
