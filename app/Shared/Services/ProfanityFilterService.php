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
    public function coTuCam(?string $vanBan): bool
    {
        if (empty($vanBan)) {
            return false;
        }

        return count($this->layDanhSachViPham($vanBan)) > 0;
    }

    /**
     * Trích xuất danh sách các từ ngữ vi phạm xuất hiện trong văn bản.
     *
     * @return array<string>
     */
    public function layDanhSachViPham(?string $vanBan): array
    {
        if (empty($vanBan)) {
            return [];
        }

        $patterns = $this->layMauBienDich();
        $vanBanChuanHoa = $this->chuanHoaVanBan($vanBan);
        $danhSachViPham = [];

        foreach ($patterns as $tu => $pattern) {
            if (preg_match($pattern, $vanBanChuanHoa) || preg_match($pattern, mb_strtolower($vanBan, 'UTF-8'))) {
                $danhSachViPham[] = $tu;
            }
        }

        return array_unique($danhSachViPham);
    }

    /**
     * Che từ cấm trong chuỗi bằng ký tự thay thế (mặc định: ***).
     */
    public function locNoiDung(?string $vanBan, string $kyTuThayThe = '***'): string
    {
        if (empty($vanBan)) {
            return '';
        }

        $patterns = $this->layMauBienDich();
        $vanBanSach = $vanBan;

        foreach ($patterns as $pattern) {
            $vanBanSach = preg_replace($pattern, $kyTuThayThe, $vanBanSach);
        }

        return $vanBanSach;
    }

    /**
     * Xóa cache từ cấm khi Admin cập nhật danh sách.
     */
    public function xoaCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Biên dịch Regex Pattern từ CSDL hoặc danh sách mặc định.
     */
    private function layMauBienDich(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(self::CACHE_TTL_MINUTES), function () {
            $tuNgu = [];

            // Linh hoạt kiểm tra tên bảng từ cấm trong CSDL (tu_cam hoặc prohibited_words)
            $tenBang = Schema::hasTable('tu_cam') ? 'tu_cam' : (Schema::hasTable('prohibited_words') ? 'prohibited_words' : null);

            if ($tenBang) {
                // Ưu tiên đọc cột tu_ngu hoặc word
                $cot = Schema::hasColumn($tenBang, 'tu_ngu') ? 'tu_ngu' : 'word';
                $tuNguTuCSDL = DB::table($tenBang)
                    ->pluck($cot)
                    ->map(fn($w) => trim((string)$w))
                    ->filter()
                    ->toArray();
                $tuNgu = array_merge($tuNgu, $tuNguTuCSDL);
            }

            if (empty($tuNgu)) {
                $tuNgu = $this->defaultWords;
            }

            $bienDich = [];
            foreach (array_unique($tuNgu) as $tuTho) {
                $tu = mb_strtolower(trim($tuTho), 'UTF-8');
                if ($tu === '') {
                    continue;
                }

                // Hỗ trợ bắt từ ngữ bị chèn khoảng trắng/dấu ngắt: đ.m, đ_m, d m
                $kyTu = preg_split('//u', preg_quote($tu, '/'), -1, PREG_SPLIT_NO_EMPTY);
                $mauThoat = implode('[\\s\\.\\_\\-\\~\\*]*', $kyTu);

                $bienDich[$tu] = '/(?<=^|[\\s\\.\\,\\;\\!\\?\\(\\)\\[\\]\\-_\\/\\\\])' . $mauThoat . '(?=$|[\\s\\.\\,\\;\\!\\?\\(\\)\\[\\]\\-_\\/\\\\])/ui';
            }

            return $bienDich;
        });
    }

    /**
     * Chuẩn hóa Leetspeak lách luật sang ký tự thông thường.
     */
    private function chuanHoaVanBan(string $vanBan): string
    {
        $chuanHoa = mb_strtolower($vanBan, 'UTF-8');

        $bangLeetspeak = [
            '0' => 'o',
            '1' => 'i',
            '3' => 'e',
            '4' => 'a',
            '@' => 'a',
            '!' => 'i',
        ];

        return strtr($chuanHoa, $bangLeetspeak);
    }
}