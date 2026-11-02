<?php

namespace App\Http\Controllers;

use App\Models\Khoa;
use App\Models\LichSuThungRac;
use App\Models\MonHoc;
use App\Models\NguoiDung;
use App\Models\TaiLieu;
use App\Models\YeuCauXacThuc;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class AdminStorageController extends Controller
{
    /**
     * Khóa bộ nhớ đệm cho dữ liệu tổng quan lưu trữ.
     */
    public const CACHE_KEY_OVERVIEW = 'admin_storage_overview';

    /**
     * Thời gian sống của bộ nhớ đệm (900 giây = 15 phút).
     */
    public const CACHE_TTL_SECONDS = 900;

    /**
     * Màn hình Bảng điều khiển Theo dõi & Quản lý Dung lượng Lưu trữ.
     */
    public function index(Request $request): Response
    {
        abort_unless($request->user()->laQuanTriVien(), 403, 'Bạn không có thẩm quyền truy cập trang quản lý lưu trữ.');

        $forceRefresh = $request->boolean('refresh');
        if ($forceRefresh) {
            $this->clearStorageCache();
        }

        // Lấy thống kê tổng hợp qua bộ nhớ đệm (Cache::remember 15 phút)
        $overview = $this->getOverviewStats($forceRefresh);

        // Ngưỡng lọc file lớn bất thường (cho phép admin tùy chỉnh ngưỡng tính theo MB trên giao diện)
        $thresholdMbInput = $request->input('large_file_threshold_mb');
        $thresholdKb = $thresholdMbInput !== null && is_numeric($thresholdMbInput) && (int) $thresholdMbInput > 0
            ? (int) $thresholdMbInput * 1024
            : null;

        $courseSearch = $request->input('course_search');
        $uploaderSearch = $request->input('uploader_search');

        return Inertia::render('Admin/Storage/Index', [
            'totalStats' => $overview['totalStats'],
            'facultyStats' => $overview['facultyStats'],
            'courseStats' => $this->calculateStorageByCourse(20, $courseSearch),
            'typeStats' => $overview['typeStats'],
            'uploaderStats' => $this->calculateStorageByUploader(20, $uploaderSearch),
            'trashStats' => $overview['trashStats'],
            'kycStats' => $overview['kycStats'],
            'largeFiles' => $this->calculateUnusuallyLargeFiles($thresholdKb, 50),
            'duplicates' => $overview['duplicates'],
            'reclaimable' => $overview['reclaimable'],
            'alert' => $overview['alert'],
            'growth' => $overview['growth'],
            'cachedAt' => $overview['cachedAt'] ?? null,
            'filters' => [
                'course_search' => $courseSearch ?? '',
                'uploader_search' => $uploaderSearch ?? '',
                'large_file_threshold_mb' => $thresholdMbInput ? (int) $thresholdMbInput : (int) (config('storage.large_file_threshold_kb', 20480) / 1024),
            ],
        ]);
    }

    /**
     * Thực thi tác vụ dọn dẹp và xóa vĩnh viễn các tài liệu trong thùng rác đã quá hạn lưu trữ.
     * Quy trình Two-Phase: ACID Transaction trong CSDL trước -> Xóa tệp vật lý sau -> Xóa cache.
     */
    public function purgeExpiredTrash(Request $request): RedirectResponse
    {
        abort_unless($request->user()->laQuanTriVien(), 403, 'Chỉ Quản trị viên mới có quyền dọn dẹp vĩnh viễn thùng rác.');

        $result = $this->executePurgeExpiredTrash($request->user());

        if ($result['so_tai_lieu_da_xoa'] === 0) {
            return back()->with('info', 'Không có tài liệu nào trong thùng rác đã hết hạn lưu trữ để xóa.');
        }

        return back()->with(
            'success',
            "Đã xóa vĩnh viễn {$result['so_tai_lieu_da_xoa']} tệp quá hạn, giải phóng {$result['dung_luong_giai_phong_dinh_dang']} dung lượng lưu trữ."
        );
    }

    /**
     * Định dạng dung lượng từ KB sang chuỗi thân thiện (B, KB, MB, GB).
     */
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

    /**
     * Lấy toàn bộ số liệu thống kê tổng quan (áp dụng chiến lược Caching 15 phút).
     */
    public function getOverviewStats(bool $forceRefresh = false): array
    {
        if ($forceRefresh) {
            $this->clearStorageCache();
        }

        return Cache::remember(self::CACHE_KEY_OVERVIEW, self::CACHE_TTL_SECONDS, function () {
            return [
                'totalStats' => $this->calculateTotalStorageStats(),
                'facultyStats' => $this->calculateStorageByFaculty(),
                'typeStats' => $this->calculateStorageByDocumentType(),
                'trashStats' => $this->calculateSoftDeletedStorageStats(),
                'kycStats' => $this->calculateKycStorageStats(),
                'duplicates' => $this->calculateDuplicateFiles(),
                'reclaimable' => $this->calculateReclaimableStorageStats(),
                'alert' => $this->calculateThresholdAlert(),
                'growth' => $this->calculateGrowthRateStats(6),
                'cachedAt' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Xóa sạch các khóa bộ nhớ đệm thống kê lưu trữ.
     */
    public function clearStorageCache(): void
    {
        Cache::forget(self::CACHE_KEY_OVERVIEW);
        Log::info('AdminStorageController: Đã xóa bộ nhớ đệm admin_storage_overview.');
    }

    // =========================================================================
    // CÁC HÀM TÍNH TOÁN NGHIỆP VỤ 12 CHỈ SỐ LƯU TRỮ (INTERNAL LOGIC)
    // =========================================================================

    /**
     * 1. Tổng hợp số liệu dung lượng file đang sử dụng toàn hệ thống.
     */
    public function calculateTotalStorageStats(): array
    {
        // 1.1 Tài liệu đang hoạt động (Active Documents)
        $activeCount = (int) TaiLieu::query()->whereNull('ngay_xoa_tam')->count();
        $activeKb = (float) (TaiLieu::query()->whereNull('ngay_xoa_tam')->sum('dung_luong_kb') ?? 0);

        // 1.2 Tài liệu đã xóa mềm trong thùng rác (Soft-deleted Documents)
        $trashedCount = (int) TaiLieu::onlyTrashed()->count();
        $trashedKb = (float) (TaiLieu::onlyTrashed()->sum('dung_luong_kb') ?? 0);

        // 1.3 Minh chứng KYC Cựu sinh viên (Private storage)
        $kycStats = $this->calculateKycStorageStats();
        $kycKb = (float) $kycStats['tong_dung_luong_kb'];
        $kycCount = (int) $kycStats['tong_so_tep'];

        // Tổng cộng dung lượng đang dùng
        $totalUsedKb = $activeKb + $trashedKb + $kycKb;
        $totalUsedMb = $totalUsedKb / 1024;

        // Hạn mức lưu trữ từ cấu hình (MB)
        $quotaMb = (float) config('storage.quota_mb', 10240);
        $usedPercent = $quotaMb > 0 ? round(($totalUsedMb / $quotaMb) * 100, 2) : 0.0;

        // Trạng thái cảnh báo
        $warningPercent = (float) config('storage.warning_threshold_percent', 80);
        $criticalPercent = (float) config('storage.critical_threshold_percent', 90);

        $status = 'AN_TOAN';
        if ($usedPercent >= $criticalPercent) {
            $status = 'NGUY_HIEM';
        } elseif ($usedPercent >= $warningPercent) {
            $status = 'CANH_BAO';
        }

        // Thông số vật lý của ổ đĩa máy chủ (nếu khả dụng)
        $diskStats = $this->getPhysicalDiskStats();

        return [
            'tong_dung_luong_kb' => round($totalUsedKb, 2),
            'tong_dung_luong_mb' => round($totalUsedMb, 2),
            'tong_dung_luong_dinh_dang' => self::formatKb($totalUsedKb),
            'han_muc_mb' => $quotaMb,
            'han_muc_dinh_dang' => self::formatKb($quotaMb * 1024),
            'ty_le_su_dung_phan_tram' => min(100, $usedPercent),
            'ty_le_su_dung_thuc_te' => $usedPercent,
            'dung_luong_con_lai_mb' => max(0, round($quotaMb - $totalUsedMb, 2)),
            'dung_luong_con_lai_dinh_dang' => self::formatKb(max(0, ($quotaMb - $totalUsedMb) * 1024)),
            'trang_thai' => $status,
            'chi_tiet' => [
                'tai_lieu_hoat_dong' => [
                    'so_luong' => $activeCount,
                    'dung_luong_kb' => round($activeKb, 2),
                    'dung_luong_dinh_dang' => self::formatKb($activeKb),
                    'ty_le_phan_tram' => $totalUsedKb > 0 ? round(($activeKb / $totalUsedKb) * 100, 1) : 0,
                ],
                'tai_lieu_xoa_mem' => [
                    'so_luong' => $trashedCount,
                    'dung_luong_kb' => round($trashedKb, 2),
                    'dung_luong_dinh_dang' => self::formatKb($trashedKb),
                    'ty_le_phan_tram' => $totalUsedKb > 0 ? round(($trashedKb / $totalUsedKb) * 100, 1) : 0,
                ],
                'minh_chung_kyc' => [
                    'so_luong' => $kycCount,
                    'dung_luong_kb' => round($kycKb, 2),
                    'dung_luong_dinh_dang' => self::formatKb($kycKb),
                    'ty_le_phan_tram' => $totalUsedKb > 0 ? round(($kycKb / $totalUsedKb) * 100, 1) : 0,
                ],
            ],
            'o_dia_vat_ly' => $diskStats,
        ];
    }

    /**
     * 2. Thống kê dung lượng theo Khoa.
     */
    public function calculateStorageByFaculty(): array
    {
        $totalDocKb = (float) (TaiLieu::withTrashed()->sum('dung_luong_kb') ?? 0);

        // Lấy danh sách thống kê gom nhóm theo Khoa (tương thích MySQL 8.0 ONLY_FULL_GROUP_BY)
        $facultyRows = DB::table('tai_lieu')
            ->join('mon_hoc', 'tai_lieu.ma_mon_hoc', '=', 'mon_hoc.ma_mon_hoc')
            ->leftJoin('khoa', 'mon_hoc.ma_khoa', '=', 'khoa.ma_khoa')
            ->select([
                'khoa.ma_khoa',
                'khoa.ky_hieu_khoa',
                'khoa.ten_khoa',
                DB::raw('COUNT(tai_lieu.ma_tai_lieu) as so_tai_lieu'),
                DB::raw('SUM(tai_lieu.dung_luong_kb) as tong_dung_luong_kb'),
                DB::raw('SUM(CASE WHEN tai_lieu.ngay_xoa_tam IS NOT NULL THEN 1 ELSE 0 END) as so_tai_lieu_xoa_mem'),
                DB::raw('SUM(CASE WHEN tai_lieu.ngay_xoa_tam IS NOT NULL THEN tai_lieu.dung_luong_kb ELSE 0 END) as dung_luong_xoa_mem_kb'),
            ])
            ->groupBy('khoa.ma_khoa', 'khoa.ky_hieu_khoa', 'khoa.ten_khoa')
            ->orderByDesc('tong_dung_luong_kb')
            ->get();

        return $facultyRows->map(function ($row) use ($totalDocKb) {
            $kb = (float) ($row->tong_dung_luong_kb ?? 0);
            $trashedKb = (float) ($row->dung_luong_xoa_mem_kb ?? 0);
            $percent = $totalDocKb > 0 ? round(($kb / $totalDocKb) * 100, 2) : 0.0;

            return [
                'ma_khoa' => $row->ma_khoa,
                'ky_hieu_khoa' => $row->ky_hieu_khoa ?? 'N/A',
                'ten_khoa' => $row->ten_khoa ?? 'Chưa phân Khoa / Khác',
                'so_tai_lieu' => (int) $row->so_tai_lieu,
                'so_tai_lieu_xoa_mem' => (int) $row->so_tai_lieu_xoa_mem,
                'tong_dung_luong_kb' => round($kb, 2),
                'dung_luong_dinh_dang' => self::formatKb($kb),
                'dung_luong_xoa_mem_dinh_dang' => self::formatKb($trashedKb),
                'ty_le_phan_tram' => $percent,
            ];
        })->toArray();
    }

    /**
     * 3. Thống kê dung lượng theo Môn học (Top courses by size + tìm kiếm).
     */
    public function calculateStorageByCourse(int $limit = 20, ?string $search = null): array
    {
        $totalDocKb = (float) (TaiLieu::withTrashed()->sum('dung_luong_kb') ?? 0);

        $query = DB::table('tai_lieu')
            ->join('mon_hoc', 'tai_lieu.ma_mon_hoc', '=', 'mon_hoc.ma_mon_hoc')
            ->leftJoin('khoa', 'mon_hoc.ma_khoa', '=', 'khoa.ma_khoa')
            ->select([
                'mon_hoc.ma_mon_hoc',
                'mon_hoc.ma_hoc_phan',
                'mon_hoc.ten_mon_hoc',
                'khoa.ten_khoa',
                'khoa.ky_hieu_khoa',
                DB::raw('COUNT(tai_lieu.ma_tai_lieu) as so_tai_lieu'),
                DB::raw('SUM(tai_lieu.dung_luong_kb) as tong_dung_luong_kb'),
                DB::raw('MAX(tai_lieu.created_at) as tai_lieu_moi_nhat'),
            ])
            ->groupBy(
                'mon_hoc.ma_mon_hoc',
                'mon_hoc.ma_hoc_phan',
                'mon_hoc.ten_mon_hoc',
                'khoa.ten_khoa',
                'khoa.ky_hieu_khoa'
            )
            ->orderByDesc('tong_dung_luong_kb');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('mon_hoc.ma_hoc_phan', 'like', "%{$search}%")
                    ->orWhere('mon_hoc.ten_mon_hoc', 'like', "%{$search}%")
                    ->orWhere('khoa.ten_khoa', 'like', "%{$search}%");
            });
        }

        $courses = $query->limit($limit)->get();

        return $courses->map(function ($row) use ($totalDocKb) {
            $kb = (float) ($row->tong_dung_luong_kb ?? 0);
            $percent = $totalDocKb > 0 ? round(($kb / $totalDocKb) * 100, 2) : 0.0;

            return [
                'ma_mon_hoc' => $row->ma_mon_hoc,
                'ma_hoc_phan' => mb_strtoupper($row->ma_hoc_phan ?? ''),
                'ten_mon_hoc' => $row->ten_mon_hoc,
                'ten_khoa' => $row->ten_khoa ?? 'N/A',
                'ky_hieu_khoa' => $row->ky_hieu_khoa ?? 'N/A',
                'so_tai_lieu' => (int) $row->so_tai_lieu,
                'tong_dung_luong_kb' => round($kb, 2),
                'dung_luong_dinh_dang' => self::formatKb($kb),
                'tai_lieu_moi_nhat' => $row->tai_lieu_moi_nhat,
                'ty_le_phan_tram' => $percent,
            ];
        })->toArray();
    }

    /**
     * 4. Thống kê dung lượng theo loại tài liệu (Phân loại & Định dạng file).
     */
    public function calculateStorageByDocumentType(): array
    {
        $totalDocKb = (float) (TaiLieu::withTrashed()->sum('dung_luong_kb') ?? 0);

        // 4.1 Phân loại theo mục đích học tập (phan_loai ENUM)
        $categoryLabels = [
            'DE_THI' => 'Đề thi & Đáp án',
            'BAI_GIANG' => 'Bài giảng / Slide',
            'DO_AN' => 'Đồ án / Khóa luận',
            'BAI_TAP' => 'Bài tập & Bài giải',
            'KHAC' => 'Tài liệu khác',
        ];

        $categoryRows = DB::table('tai_lieu')
            ->select([
                'phan_loai',
                DB::raw('COUNT(ma_tai_lieu) as so_tai_lieu'),
                DB::raw('SUM(dung_luong_kb) as tong_dung_luong_kb'),
            ])
            ->groupBy('phan_loai')
            ->orderByDesc('tong_dung_luong_kb')
            ->get();

        $byCategory = $categoryRows->map(function ($row) use ($categoryLabels, $totalDocKb) {
            $kb = (float) ($row->tong_dung_luong_kb ?? 0);
            $percent = $totalDocKb > 0 ? round(($kb / $totalDocKb) * 100, 2) : 0.0;

            return [
                'phan_loai' => $row->phan_loai,
                'ten_loai' => $categoryLabels[$row->phan_loai] ?? $row->phan_loai,
                'so_tai_lieu' => (int) $row->so_tai_lieu,
                'tong_dung_luong_kb' => round($kb, 2),
                'dung_luong_dinh_dang' => self::formatKb($kb),
                'ty_le_phan_tram' => $percent,
            ];
        })->toArray();

        // 4.2 Phân loại theo phần mở rộng tệp (dinh_dang_tep: pdf, docx, zip...)
        $extensionRows = DB::table('tai_lieu')
            ->select([
                'dinh_dang_tep',
                DB::raw('COUNT(ma_tai_lieu) as so_tai_lieu'),
                DB::raw('SUM(dung_luong_kb) as tong_dung_luong_kb'),
            ])
            ->groupBy('dinh_dang_tep')
            ->orderByDesc('tong_dung_luong_kb')
            ->limit(10)
            ->get();

        $byExtension = $extensionRows->map(function ($row) use ($totalDocKb) {
            $kb = (float) ($row->tong_dung_luong_kb ?? 0);
            $percent = $totalDocKb > 0 ? round(($kb / $totalDocKb) * 100, 2) : 0.0;
            $ext = strtolower($row->dinh_dang_tep ?? 'khác');

            return [
                'dinh_dang_tep' => $ext,
                'so_tai_lieu' => (int) $row->so_tai_lieu,
                'tong_dung_luong_kb' => round($kb, 2),
                'dung_luong_dinh_dang' => self::formatKb($kb),
                'ty_le_phan_tram' => $percent,
            ];
        })->toArray();

        return [
            'theo_muc_dich' => $byCategory,
            'theo_dinh_dang' => $byExtension,
        ];
    }

    /**
     * 5. Thống kê dung lượng theo người đăng (Top uploaders).
     */
    public function calculateStorageByUploader(int $limit = 20, ?string $search = null): array
    {
        $totalDocKb = (float) (TaiLieu::withTrashed()->sum('dung_luong_kb') ?? 0);

        $query = DB::table('tai_lieu')
            ->join('nguoi_dung', 'tai_lieu.ma_nguoi_dang', '=', 'nguoi_dung.ma_nguoi_dung')
            ->leftJoin('khoa', 'nguoi_dung.ma_khoa', '=', 'khoa.ma_khoa')
            ->select([
                'nguoi_dung.ma_nguoi_dung',
                'nguoi_dung.ho_ten',
                'nguoi_dung.email',
                'nguoi_dung.mssv',
                'nguoi_dung.vai_tro',
                'khoa.ten_khoa',
                DB::raw('COUNT(tai_lieu.ma_tai_lieu) as tong_so_file'),
                DB::raw('SUM(CASE WHEN tai_lieu.ngay_xoa_tam IS NULL THEN 1 ELSE 0 END) as so_file_hoat_dong'),
                DB::raw('SUM(CASE WHEN tai_lieu.ngay_xoa_tam IS NOT NULL THEN 1 ELSE 0 END) as so_file_xoa_mem'),
                DB::raw('SUM(tai_lieu.dung_luong_kb) as tong_dung_luong_kb'),
            ])
            ->groupBy(
                'nguoi_dung.ma_nguoi_dung',
                'nguoi_dung.ho_ten',
                'nguoi_dung.email',
                'nguoi_dung.mssv',
                'nguoi_dung.vai_tro',
                'khoa.ten_khoa'
            )
            ->orderByDesc('tong_dung_luong_kb');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nguoi_dung.ho_ten', 'like', "%{$search}%")
                    ->orWhere('nguoi_dung.email', 'like', "%{$search}%")
                    ->orWhere('nguoi_dung.mssv', 'like', "%{$search}%");
            });
        }

        $uploaders = $query->limit($limit)->get();

        return $uploaders->map(function ($row) use ($totalDocKb) {
            $kb = (float) ($row->tong_dung_luong_kb ?? 0);
            $percent = $totalDocKb > 0 ? round(($kb / $totalDocKb) * 100, 2) : 0.0;

            return [
                'ma_nguoi_dung' => $row->ma_nguoi_dung,
                'ho_ten' => $row->ho_ten,
                'email' => $row->email,
                'mssv' => $row->mssv,
                'vai_tro' => $row->vai_tro,
                'ten_khoa' => $row->ten_khoa ?? 'N/A',
                'tong_so_file' => (int) $row->tong_so_file,
                'so_file_hoat_dong' => (int) $row->so_file_hoat_dong,
                'so_file_xoa_mem' => (int) $row->so_file_xoa_mem,
                'tong_dung_luong_kb' => round($kb, 2),
                'dung_luong_dinh_dang' => self::formatKb($kb),
                'ty_le_phan_tram' => $percent,
            ];
        })->toArray();
    }

    /**
     * 6. Dung lượng tài liệu đã xóa mềm trong thùng rác.
     */
    public function calculateSoftDeletedStorageStats(): array
    {
        $trashedQuery = TaiLieu::onlyTrashed();

        $totalCount = (int) (clone $trashedQuery)->count();
        $totalKb = (float) ((clone $trashedQuery)->sum('dung_luong_kb') ?? 0);

        // File đã hết hạn lưu thùng rác (đủ điều kiện xóa vĩnh viễn giải phóng dung lượng)
        $now = now();
        $expiredQuery = (clone $trashedQuery)->where('han_luu_thung_rac', '<=', $now);
        $expiredCount = (int) (clone $expiredQuery)->count();
        $expiredKb = (float) ((clone $expiredQuery)->sum('dung_luong_kb') ?? 0);

        // File còn trong thời gian ân hạn khôi phục
        $graceQuery = (clone $trashedQuery)->where(function ($q) use ($now) {
            $q->where('han_luu_thung_rac', '>', $now)
                ->orWhereNull('han_luu_thung_rac');
        });
        $graceCount = (int) (clone $graceQuery)->count();
        $graceKb = (float) ((clone $graceQuery)->sum('dung_luong_kb') ?? 0);

        $retentionDays = (int) config('documents.trash_admin_delete_after_days', 60);

        return [
            'tong_so_tai_lieu' => $totalCount,
            'tong_dung_luong_kb' => round($totalKb, 2),
            'tong_dung_luong_dinh_dang' => self::formatKb($totalKb),
            'da_het_han' => [
                'so_luong' => $expiredCount,
                'dung_luong_kb' => round($expiredKb, 2),
                'dung_luong_dinh_dang' => self::formatKb($expiredKb),
                'co_the_xoa_ngay' => $expiredCount > 0,
            ],
            'trong_an_han' => [
                'so_luong' => $graceCount,
                'dung_luong_kb' => round($graceKb, 2),
                'dung_luong_dinh_dang' => self::formatKb($graceKb),
            ],
            'thoi_han_luu_ngay' => $retentionDays,
        ];
    }

    /**
     * 7. Dung lượng khu vực private chứa minh chứng Alumni KYC.
     * BẢO MẬT PII: Tuyệt đối không trả về đường dẫn vật lý thực tế trên máy chủ.
     */
    public function calculateKycStorageStats(): array
    {
        $diskName = config('storage.kyc_disk', 'local');
        $kycDir = config('storage.kyc_path', 'private/kyc');

        $disk = Storage::disk($diskName);

        // Quét tệp vật lý thực tế trên đĩa nội bộ
        $physicalFiles = [];
        $physicalTotalBytes = 0;

        try {
            if ($disk->exists($kycDir)) {
                $physicalFiles = $disk->allFiles($kycDir);
                foreach ($physicalFiles as $file) {
                    $physicalTotalBytes += (int) $disk->size($file);
                }
            }
        } catch (\Throwable $e) {
            $physicalTotalBytes = 0;
        }

        // Thống kê theo hồ sơ Yêu cầu xác thực trong CSDL
        $kycRows = DB::table('yeu_cau_xac_thuc')
            ->select([
                'trang_thai',
                DB::raw('COUNT(ma_yeu_cau_xac_thuc) as so_luong'),
                DB::raw('MAX(created_at) as moi_nhat'),
                DB::raw('MIN(created_at) as cu_nhat'),
            ])
            ->groupBy('trang_thai')
            ->get();

        $totalDbCount = (int) YeuCauXacThuc::query()->count();
        $physicalKb = round($physicalTotalBytes / 1024, 2);

        $byStatus = [
            'CHO_DUYET' => ['so_luong' => 0, 'dung_luong_kb' => 0, 'dung_luong_dinh_dang' => '0 KB'],
            'DA_DUYET' => ['so_luong' => 0, 'dung_luong_kb' => 0, 'dung_luong_dinh_dang' => '0 KB'],
            'TU_CHOI' => ['so_luong' => 0, 'dung_luong_kb' => 0, 'dung_luong_dinh_dang' => '0 KB'],
        ];

        $avgSizeKb = $totalDbCount > 0 ? ($physicalKb / $totalDbCount) : 0;

        foreach ($kycRows as $row) {
            $count = (int) $row->so_luong;
            $statusKb = round($count * $avgSizeKb, 2);
            $byStatus[$row->trang_thai] = [
                'so_luong' => $count,
                'dung_luong_kb' => $statusKb,
                'dung_luong_dinh_dang' => self::formatKb($statusKb),
                'moi_nhat' => $row->moi_nhat,
                'cu_nhat' => $row->cu_nhat,
            ];
        }

        return [
            'khu_vuc_bao_mat' => 'Thư mục Private (Chỉ truy cập qua Signed URL 30 phút)',
            'tong_so_tep' => max(count($physicalFiles), $totalDbCount),
            'tong_so_ho_so_csdl' => $totalDbCount,
            'tong_dung_luong_bytes' => $physicalTotalBytes,
            'tong_dung_luong_kb' => $physicalKb,
            'tong_dung_luong_dinh_dang' => self::formatKb($physicalKb),
            'kich_thuoc_trung_binh_kb' => round($avgSizeKb, 2),
            'kich_thuoc_trung_binh_dinh_dang' => self::formatKb($avgSizeKb),
            'theo_trang_thai' => $byStatus,
        ];
    }

    /**
     * 8. Danh sách file có dung lượng lớn bất thường.
     */
    public function calculateUnusuallyLargeFiles(?int $thresholdKb = null, int $limit = 50): array
    {
        $threshold = $thresholdKb ?? (int) config('storage.large_file_threshold_kb', 20480);

        $largeFiles = TaiLieu::withTrashed()
            ->with(['monHoc.khoa', 'nguoiDang'])
            ->where('dung_luong_kb', '>=', $threshold)
            ->orderByDesc('dung_luong_kb')
            ->limit($limit)
            ->get();

        return [
            'nguong_kb' => $threshold,
            'nguong_dinh_dang' => self::formatKb($threshold),
            'tong_so_tep_lon' => $largeFiles->count(),
            'tong_dung_luong_tep_lon_kb' => round((float) $largeFiles->sum('dung_luong_kb'), 2),
            'tong_dung_luong_tep_lon_dinh_dang' => self::formatKb((float) $largeFiles->sum('dung_luong_kb')),
            'danh_sach' => $largeFiles->map(function (TaiLieu $doc) {
                $isTrashed = $doc->trashed();
                $sizeKb = (float) $doc->dung_luong_kb;

                // Mức độ cảnh báo kích thước file
                $severity = 'warning';
                if ($sizeKb >= 51200) { // >= 50MB
                    $severity = 'critical';
                }

                return [
                    'ma_tai_lieu' => $doc->ma_tai_lieu,
                    'tieu_de' => $doc->tieu_de,
                    'dinh_dang_tep' => strtolower($doc->dinh_dang_tep),
                    'dung_luong_kb' => $sizeKb,
                    'dung_luong_dinh_dang' => self::formatKb($sizeKb),
                    'phan_loai' => $doc->phan_loai,
                    'ma_hoc_phan' => mb_strtoupper($doc->monHoc?->ma_hoc_phan ?? ''),
                    'ten_mon_hoc' => $doc->monHoc?->ten_mon_hoc ?? 'N/A',
                    'ten_khoa' => $doc->monHoc?->khoa?->ten_khoa ?? 'N/A',
                    'ky_hieu_khoa' => $doc->monHoc?->khoa?->ky_hieu_khoa ?? 'N/A',
                    'nguoi_dang' => [
                        'ma_nguoi_dung' => $doc->nguoiDang?->ma_nguoi_dung,
                        'ho_ten' => $doc->nguoiDang?->ho_ten ?? 'Không rõ',
                        'email' => $doc->nguoiDang?->email,
                    ],
                    'trang_thai' => $doc->trang_thai,
                    'da_xoa_tam' => $isTrashed,
                    'ngay_xoa_tam' => $doc->ngay_xoa_tam?->toIso8601String(),
                    'created_at' => $doc->created_at?->toIso8601String(),
                    'muc_do_canh_bao' => $severity,
                ];
            })->toArray(),
        ];
    }

    /**
     * 9. Danh sách file trùng lặp (Group by SHA-256 hash).
     */
    public function calculateDuplicateFiles(): array
    {
        // Tìm các nhóm mã băm file có từ 2 bản sao trở lên
        $duplicateHashes = DB::table('tai_lieu')
            ->select('ma_bam_file', DB::raw('COUNT(ma_tai_lieu) as so_ban_sao'))
            ->whereNotNull('ma_bam_file')
            ->where('ma_bam_file', '!=', '')
            ->groupBy('ma_bam_file')
            ->havingRaw('COUNT(ma_tai_lieu) > 1')
            ->orderByDesc('so_ban_sao')
            ->get();

        $clusters = [];
        $totalDuplicateCopies = 0;
        $totalWastedKb = 0.0;

        foreach ($duplicateHashes as $dup) {
            $hash = $dup->ma_bam_file;
            $copiesCount = (int) $dup->so_ban_sao;

            // Lấy danh sách các tài liệu mang mã băm này
            $docs = TaiLieu::withTrashed()
                ->with(['monHoc', 'nguoiDang'])
                ->where('ma_bam_file', $hash)
                ->orderBy('created_at')
                ->get();

            if ($docs->isEmpty()) {
                continue;
            }

            $firstDoc = $docs->first();
            $singleFileSizeKb = (float) $firstDoc->dung_luong_kb;
            $wastedCopies = max(0, $copiesCount - 1);
            $wastedSpaceKb = $wastedCopies * $singleFileSizeKb;

            $totalDuplicateCopies += $wastedCopies;
            $totalWastedKb += $wastedSpaceKb;

            $clusters[] = [
                'ma_bam_file' => $hash,
                'so_ban_sao' => $copiesCount,
                'so_ban_trung_thua' => $wastedCopies,
                'dung_luong_file_kb' => $singleFileSizeKb,
                'dung_luong_file_dinh_dang' => self::formatKb($singleFileSizeKb),
                'dung_luong_lang_phi_kb' => round($wastedSpaceKb, 2),
                'dung_luong_lang_phi_dinh_dang' => self::formatKb($wastedSpaceKb),
                'dinh_dang_tep' => strtolower($firstDoc->dinh_dang_tep),
                'danh_sach_tai_lieu' => $docs->map(fn(TaiLieu $d) => [
                    'ma_tai_lieu' => $d->ma_tai_lieu,
                    'tieu_de' => $d->tieu_de,
                    'ma_hoc_phan' => mb_strtoupper($d->monHoc?->ma_hoc_phan ?? ''),
                    'ten_mon_hoc' => $d->monHoc?->ten_mon_hoc ?? 'N/A',
                    'nguoi_dang' => $d->nguoiDang?->ho_ten ?? 'Không rõ',
                    'created_at' => $d->created_at?->toIso8601String(),
                    'trang_thai' => $d->trang_thai,
                    'da_xoa_tam' => $d->trashed(),
                ])->toArray(),
            ];
        }

        // Sắp xếp các cụm theo dung lượng lãng phí nhiều nhất
        usort($clusters, fn($a, $b) => $b['dung_luong_lang_phi_kb'] <=> $a['dung_luong_lang_phi_kb']);

        return [
            'tong_so_cum_trung_lap' => count($clusters),
            'tong_so_ban_trung_thua' => $totalDuplicateCopies,
            'tong_dung_luong_lang_phi_kb' => round($totalWastedKb, 2),
            'tong_dung_luong_lang_phi_dinh_dang' => self::formatKb($totalWastedKb),
            'cum_trung_lap' => $clusters,
        ];
    }

    /**
     * 10. Dung lượng có thể giải phóng (Cleanable / Reclaimable Storage).
     */
    public function calculateReclaimableStorageStats(): array
    {
        // 10.1 Dung lượng từ file trùng lặp
        $dupStats = $this->calculateDuplicateFiles();
        $dupWastedKb = (float) $dupStats['tong_dung_luong_lang_phi_kb'];

        // 10.2 Dung lượng từ tài liệu xóa mềm hết hạn trong thùng rác
        $trashStats = $this->calculateSoftDeletedStorageStats();
        $expiredTrashKb = (float) $trashStats['da_het_han']['dung_luong_kb'];
        $allTrashKb = (float) $trashStats['tong_dung_luong_kb'];

        // Tổng dung lượng có thể giải phóng ngay lập tức (Hết hạn lưu trữ + file trùng lặp)
        $immediateReclaimableKb = $expiredTrashKb + $dupWastedKb;

        // Tổng dung lượng có thể giải phóng tối đa (Toàn bộ thùng rác + file trùng lặp)
        $maxReclaimableKb = $allTrashKb + $dupWastedKb;

        $totalStorageStats = $this->calculateTotalStorageStats();
        $totalUsedKb = (float) $totalStorageStats['tong_dung_luong_kb'];

        $immediatePercent = $totalUsedKb > 0 ? round(($immediateReclaimableKb / $totalUsedKb) * 100, 1) : 0.0;
        $maxPercent = $totalUsedKb > 0 ? round(($maxReclaimableKb / $totalUsedKb) * 100, 1) : 0.0;

        return [
            'giai_phong_ngay' => [
                'dung_luong_kb' => round($immediateReclaimableKb, 2),
                'dung_luong_dinh_dang' => self::formatKb($immediateReclaimableKb),
                'ty_le_phan_tram' => $immediatePercent,
                'so_file_thung_rac_het_han' => $trashStats['da_het_han']['so_luong'],
                'so_file_trung_lap' => $dupStats['tong_so_ban_trung_thua'],
            ],
            'giai_phong_toi_da' => [
                'dung_luong_kb' => round($maxReclaimableKb, 2),
                'dung_luong_dinh_dang' => self::formatKb($maxReclaimableKb),
                'ty_le_phan_tram' => $maxPercent,
                'so_file_thung_rac_tat_ca' => $trashStats['tong_so_tai_lieu'],
            ],
            'chi_tiet' => [
                'thung_rac_het_han_kb' => $expiredTrashKb,
                'thung_rac_het_han_dinh_dang' => self::formatKb($expiredTrashKb),
                'thung_rac_tat_ca_kb' => $allTrashKb,
                'thung_rac_tat_ca_dinh_dang' => self::formatKb($allTrashKb),
                'tep_trung_lap_kb' => $dupWastedKb,
                'tep_trung_lap_dinh_dang' => self::formatKb($dupWastedKb),
            ],
        ];
    }

    /**
     * 11. Cảnh báo khi dung lượng vượt ngưỡng.
     */
    public function calculateThresholdAlert(): array
    {
        $stats = $this->calculateTotalStorageStats();
        $usedMb = (float) $stats['tong_dung_luong_mb'];
        $quotaMb = (float) $stats['han_muc_mb'];
        $usedPercent = (float) $stats['ty_le_su_dung_thuc_te'];

        $warningPercent = (float) config('storage.warning_threshold_percent', 80);
        $criticalPercent = (float) config('storage.critical_threshold_percent', 90);

        $severity = 'safe';
        $title = 'Hệ thống lưu trữ đang trong giới hạn an toàn';
        $message = "Dung lượng hiện tại đang chiếm {$usedPercent}% so với hạn mức cho phép ({$stats['tong_dung_luong_dinh_dang']} / {$stats['han_muc_dinh_dang']}).";
        $recommendations = [];

        if ($usedPercent >= $criticalPercent) {
            $severity = 'critical';
            $title = 'CẢNH BÁO NGUY CẤP: Dung lượng lưu trữ đã vượt ngưỡng đỏ!';
            $message = "Dung lượng đã sử dụng {$usedPercent}% (vượt ngưỡng đỏ {$criticalPercent}%). Hệ thống có nguy cơ từ chối tải lên tệp mới nếu không được giải phóng kịp thời.";
            $recommendations = [
                'Thực hiện ngay tác vụ dọn dẹp các tệp đã hết hạn lưu trong Thùng rác.',
                'Kiểm tra và xử lý danh sách tệp trùng lặp để tiết kiệm tài nguyên.',
                'Rà soát các tệp dung lượng lớn bất thường và liên hệ tác giả tối ưu hóa file nén/PDF.',
                'Xem xét nâng cấp hạn mức ổ đĩa máy chủ lưu trữ (STORAGE_QUOTA_MB).',
            ];
        } elseif ($usedPercent >= $warningPercent) {
            $severity = 'warning';
            $title = 'CẢNH BÁO: Dung lượng lưu trữ sắp đạt ngưỡng tối đa';
            $message = "Dung lượng đã sử dụng {$usedPercent}% (vượt ngưỡng cảnh báo vàng {$warningPercent}%). Cần theo dõi sát tốc độ tăng trưởng lưu trữ.";
            $recommendations = [
                'Dọn dẹp các tệp lưu tạm trong thùng rác đã quá hạn lưu trữ.',
                'Xem xét rà soát các tệp tải lên có dung lượng lớn.',
            ];
        }

        return [
            'muc_do' => $severity, // safe, warning, critical
            'tieu_de' => $title,
            'thong_diep' => $message,
            'ty_le_hien_tai' => $usedPercent,
            'nguong_canh_bao_vang' => $warningPercent,
            'nguong_canh_bao_do' => $criticalPercent,
            'khuyen_nghi' => $recommendations,
            'can_canh_bao' => $severity !== 'safe',
        ];
    }

    /**
     * 12. Thống kê tốc độ tăng dung lượng theo thời gian (Growth Rate & Forecast).
     * Tương thích đa nền tảng CSDL: Tự động phân nhánh MySQL (DATE_FORMAT) vs SQLite (strftime).
     */
    public function calculateGrowthRateStats(int $months = 6): array
    {
        $driver = DB::connection()->getDriverName();
        $dateExpr = $driver === 'sqlite'
            ? "strftime('%Y-%m', created_at)"
            : "DATE_FORMAT(created_at, '%Y-%m')";

        $startDate = Carbon::now()->subMonths($months - 1)->startOfMonth();

        // Lấy số liệu nhóm theo tháng (tương thích strict ONLY_FULL_GROUP_BY)
        $monthlyRecords = DB::table('tai_lieu')
            ->select([
                DB::raw("{$dateExpr} as thang"),
                DB::raw('COUNT(ma_tai_lieu) as so_file_moi'),
                DB::raw('SUM(dung_luong_kb) as dung_luong_moi_kb'),
            ])
            ->where('created_at', '>=', $startDate)
            ->groupBy(DB::raw("{$dateExpr}"))
            ->orderBy(DB::raw("{$dateExpr}"))
            ->get()
            ->keyBy('thang');

        // Tạo mảng liên tục đủ $months tháng gần nhất
        $timeline = [];
        $cumulativeKb = (float) (TaiLieu::withTrashed()
            ->where('created_at', '<', $startDate)
            ->sum('dung_luong_kb') ?? 0);

        $previousMonthKb = null;
        $allMonthlyKb = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $monthKey = Carbon::now()->subMonths($i)->format('Y-m');
            $record = $monthlyRecords->get($monthKey);

            $newFiles = $record ? (int) $record->so_file_moi : 0;
            $newKb = $record ? (float) $record->dung_luong_moi_kb : 0.0;

            $cumulativeKb += $newKb;
            $allMonthlyKb[] = $newKb;

            // Tính % tăng trưởng so với tháng trước
            $growthRatePercent = null;
            if ($previousMonthKb !== null) {
                if ($previousMonthKb > 0) {
                    $growthRatePercent = round((($newKb - $previousMonthKb) / $previousMonthKb) * 100, 1);
                } elseif ($newKb > 0) {
                    $growthRatePercent = 100.0;
                } else {
                    $growthRatePercent = 0.0;
                }
            }

            $timeline[] = [
                'thang' => $monthKey,
                'nhan_thoi_gian' => Carbon::createFromFormat('Y-m', $monthKey)->translatedFormat('M Y'),
                'so_file_moi' => $newFiles,
                'dung_luong_moi_kb' => round($newKb, 2),
                'dung_luong_moi_dinh_dang' => self::formatKb($newKb),
                'dung_luong_tich_luy_kb' => round($cumulativeKb, 2),
                'dung_luong_tich_luy_dinh_dang' => self::formatKb($cumulativeKb),
                'toc_do_tang_truong_phan_tram' => $growthRatePercent,
            ];

            $previousMonthKb = $newKb;
        }

        // Tốc độ tăng trưởng trung bình mỗi tháng và mỗi ngày
        $avgMonthlyKb = count($allMonthlyKb) > 0 ? (array_sum($allMonthlyKb) / count($allMonthlyKb)) : 0.0;
        $avgDailyKb = $avgMonthlyKb / 30;

        // Dự báo số ngày còn lại đến khi chạm hạn mức
        $totalStats = $this->calculateTotalStorageStats();
        $remainingKb = max(0, ($totalStats['han_muc_mb'] * 1024) - $totalStats['tong_dung_luong_kb']);

        $estimatedDaysRemaining = null;
        if ($avgDailyKb > 0) {
            $estimatedDaysRemaining = (int) round($remainingKb / $avgDailyKb);
        }

        return [
            'so_thang_thong_ke' => $months,
            'dong_thoi_gian' => $timeline,
            'toc_do_trung_binh_thang_kb' => round($avgMonthlyKb, 2),
            'toc_do_trung_binh_thang_dinh_dang' => self::formatKb($avgMonthlyKb),
            'toc_do_trung_binh_ngay_kb' => round($avgDailyKb, 2),
            'toc_do_trung_binh_ngay_dinh_dang' => self::formatKb($avgDailyKb),
            'du_bao_so_ngay_con_lai' => $estimatedDaysRemaining,
        ];
    }

    /**
     * Xóa vĩnh viễn các tài liệu trong thùng rác đã quá hạn lưu trữ.
     * TÍNH TOÀN VẸN GIAO DỊCH (ACID & TWO-PHASE PURGE).
     */
    public function executePurgeExpiredTrash(NguoiDung $actor): array
    {
        $filesToDelete = [];
        $purgedCount = 0;
        $freedKb = 0.0;

        // Giai đoạn 1: Giao dịch CSDL an toàn (Atomic Transaction)
        DB::transaction(function () use ($actor, &$filesToDelete, &$purgedCount, &$freedKb): void {
            $now = now();
            $expiredDocs = TaiLieu::onlyTrashed()
                ->where('han_luu_thung_rac', '<=', $now)
                ->lockForUpdate()
                ->get();

            foreach ($expiredDocs as $doc) {
                $fileKb = (float) $doc->dung_luong_kb;
                $filePath = $doc->duong_dan_tep;

                // Ghi vết vào kiểm toán Thùng rác (bắt buộc theo quy định kiểm toán AGU)
                LichSuThungRac::create([
                    'ma_tai_lieu' => $doc->getKey(),
                    'ma_nguoi_thuc_hien' => $actor->getKey(),
                    'hanh_dong' => 'PERMANENT_DELETE',
                    'nguon' => 'SYSTEM_PURGE',
                    'ly_do' => 'Hệ thống tự động xóa vĩnh viễn do đã quá hạn lưu trữ thùng rác.',
                    'chi_tiet' => [
                        'dung_luong_kb' => $fileKb,
                        'duong_dan_tep' => $filePath,
                    ],
                ]);

                // Xóa vĩnh viễn bản ghi khỏi CSDL
                $doc->forceDelete();

                // Lưu đường dẫn file vật lý để xóa sau khi transaction CSDL commit
                if ($filePath) {
                    $filesToDelete[] = $filePath;
                }

                $purgedCount++;
                $freedKb += $fileKb;
            }
        });

        // Giai đoạn 2: Xóa tệp vật lý trên đĩa sau khi CSDL đã commit hoàn tất
        $disk = Storage::disk(config('storage.document_disk', 'local'));
        foreach ($filesToDelete as $filePath) {
            try {
                if ($filePath && $disk->exists($filePath)) {
                    $disk->delete($filePath);
                }
            } catch (\Throwable $e) {
                Log::warning("AdminStorageController: Lỗi xóa file vật lý sau khi purge: {$filePath}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Giai đoạn 3: Xóa cache ngay lập tức để cập nhật số liệu mới trên Dashboard
        $this->clearStorageCache();

        return [
            'so_tai_lieu_da_xoa' => $purgedCount,
            'dung_luong_giai_phong_kb' => round($freedKb, 2),
            'dung_luong_giai_phong_dinh_dang' => self::formatKb($freedKb),
        ];
    }

    /**
     * Lấy thông số ổ đĩa vật lý của máy chủ nếu hệ điều hành hỗ trợ.
     */
    private function getPhysicalDiskStats(): ?array
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
                    'tong_dung_luong_o_dia_dinh_dang' => self::formatKb($totalKb),
                    'con_trong_dinh_dang' => self::formatKb($freeKb),
                    'da_dung_dinh_dang' => self::formatKb($usedKb),
                    'ty_le_da_dung_phan_tram' => round(($usedBytes / $totalBytes) * 100, 1),
                ];
            }
        } catch (\Throwable $e) {
            // Không khả dụng trên một số môi trường ảo hóa/sandbox
        }

        return null;
    }
}
