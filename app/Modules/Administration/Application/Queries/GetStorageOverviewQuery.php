<?php

namespace App\Modules\Administration\Application\Queries;

use App\Modules\Administration\Domain\Services\StorageThresholdEvaluator;
use App\Modules\Administration\Domain\ValueObjects\StorageMetric;
use App\Modules\Administration\Infrastructure\Persistence\StorageStatsRepository;
use App\Modules\Administration\Infrastructure\Storage\PhysicalStorageScanner;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GetStorageOverviewQuery
{
    public const CACHE_KEY_OVERVIEW = 'admin_storage_overview';
    public const CACHE_TTL_SECONDS = 900;

    public function __construct(
        protected StorageStatsRepository $repository,
        protected PhysicalStorageScanner $scanner,
        protected StorageThresholdEvaluator $evaluator
    ) {}

    // Thực thi truy vấn tổng quan dung lượng với bộ nhớ đệm (Cache 15 phút)
    public function execute(bool $forceRefresh = false): array
    {
        if ($forceRefresh) {
            $this->clearCache();
        }

        return Cache::remember(self::CACHE_KEY_OVERVIEW, self::CACHE_TTL_SECONDS, function () {
            $kycStats = $this->calculateKycStorageStats();
            $totalStats = $this->calculateTotalStorageStats($kycStats);
            $facultyStats = $this->calculateStorageByFaculty();
            $typeStats = $this->calculateStorageByDocumentType();
            $trashStats = $this->calculateSoftDeletedStorageStats();
            $duplicates = $this->calculateDuplicateFiles();
            $reclaimable = $this->calculateReclaimableStorageStats($totalStats, $trashStats, $duplicates);
            $alert = $this->calculateThresholdAlert($totalStats);
            $growth = $this->calculateGrowthRateStats($totalStats, 6);

            return [
                'totalStats' => $totalStats,
                'facultyStats' => $facultyStats,
                'typeStats' => $typeStats,
                'trashStats' => $trashStats,
                'kycStats' => $kycStats,
                'duplicates' => $duplicates,
                'reclaimable' => $reclaimable,
                'alert' => $alert,
                'growth' => $growth,
                'cachedAt' => now()->toIso8601String(),
            ];
        });
    }

    // Xóa sạch cache tổng quan lưu trữ
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY_OVERVIEW);
        Log::info('GetStorageOverviewQuery: Đã xóa bộ nhớ đệm admin_storage_overview.');
    }

    // Lấy riêng số liệu tổng lưu trữ (dành cho Admin Dashboard Widget)
    public function getTotalStorageStats(): array
    {
        $cached = Cache::get(self::CACHE_KEY_OVERVIEW);
        if ($cached && isset($cached['totalStats'])) {
            return $cached['totalStats'];
        }

        $kycStats = $this->calculateKycStorageStats();
        return $this->calculateTotalStorageStats($kycStats);
    }

    // 1. Tổng hợp dung lượng toàn hệ thống
    public function calculateTotalStorageStats(?array $kycStats = null): array
    {
        $active = $this->repository->getActiveDocumentsStats();
        $trashed = $this->repository->getTrashedDocumentsStats();

        if ($kycStats === null) {
            $kycStats = $this->calculateKycStorageStats();
        }

        $activeKb = (float) $active['kb'];
        $activeCount = (int) $active['count'];

        $trashedKb = (float) $trashed['kb'];
        $trashedCount = (int) $trashed['count'];

        $kycKb = (float) $kycStats['tong_dung_luong_kb'];
        $kycCount = (int) $kycStats['tong_so_tep'];

        $totalUsedKb = $activeKb + $trashedKb + $kycKb;
        $totalUsedMb = StorageMetric::kbToMb($totalUsedKb);

        $quotaMb = (float) config('storage.quota_mb', 10240);
        $usedPercent = $quotaMb > 0 ? round(($totalUsedMb / $quotaMb) * 100, 2) : 0.0;

        $warningPercent = (float) config('storage.warning_threshold_percent', 80);
        $criticalPercent = (float) config('storage.critical_threshold_percent', 90);

        $status = $this->evaluator->determineStatus($usedPercent, $warningPercent, $criticalPercent);
        $diskStats = $this->scanner->getPhysicalDiskStats();

        return [
            'tong_dung_luong_kb' => round($totalUsedKb, 2),
            'tong_dung_luong_mb' => round($totalUsedMb, 2),
            'tong_dung_luong_dinh_dang' => StorageMetric::formatKb($totalUsedKb),
            'han_muc_mb' => $quotaMb,
            'han_muc_dinh_dang' => StorageMetric::formatKb(StorageMetric::mbToKb($quotaMb)),
            'ty_le_su_dung_phan_tram' => min(100, $usedPercent),
            'ty_le_su_dung_thuc_te' => $usedPercent,
            'dung_luong_con_lai_mb' => max(0, round($quotaMb - $totalUsedMb, 2)),
            'dung_luong_con_lai_dinh_dang' => StorageMetric::formatKb(max(0, StorageMetric::mbToKb($quotaMb - $totalUsedMb))),
            'trang_thai' => $status,
            'chi_tiet' => [
                'tai_lieu_hoat_dong' => [
                    'so_luong' => $activeCount,
                    'dung_luong_kb' => round($activeKb, 2),
                    'dung_luong_dinh_dang' => StorageMetric::formatKb($activeKb),
                    'ty_le_phan_tram' => $totalUsedKb > 0 ? round(($activeKb / $totalUsedKb) * 100, 1) : 0,
                ],
                'tai_lieu_xoa_mem' => [
                    'so_luong' => $trashedCount,
                    'dung_luong_kb' => round($trashedKb, 2),
                    'dung_luong_dinh_dang' => StorageMetric::formatKb($trashedKb),
                    'ty_le_phan_tram' => $totalUsedKb > 0 ? round(($trashedKb / $totalUsedKb) * 100, 1) : 0,
                ],
                'minh_chung_kyc' => [
                    'so_luong' => $kycCount,
                    'dung_luong_kb' => round($kycKb, 2),
                    'dung_luong_dinh_dang' => StorageMetric::formatKb($kycKb),
                    'ty_le_phan_tram' => $totalUsedKb > 0 ? round(($kycKb / $totalUsedKb) * 100, 1) : 0,
                ],
            ],
            'o_dia_vat_ly' => $diskStats,
        ];
    }

    // 2. Thống kê theo Khoa
    public function calculateStorageByFaculty(): array
    {
        $totalDocKb = $this->repository->getTotalAllDocumentsKb();
        $rows = $this->repository->getStorageByFaculty();

        return $rows->map(function ($row) use ($totalDocKb) {
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
                'dung_luong_dinh_dang' => StorageMetric::formatKb($kb),
                'dung_luong_xoa_mem_dinh_dang' => StorageMetric::formatKb($trashedKb),
                'ty_le_phan_tram' => $percent,
            ];
        })->toArray();
    }

    // 4. Thống kê theo loại tài liệu
    public function calculateStorageByDocumentType(): array
    {
        $totalDocKb = $this->repository->getTotalAllDocumentsKb();

        $categoryLabels = [
            'DE_THI' => 'Đề thi & Đáp án',
            'BAI_GIANG' => 'Bài giảng / Slide',
            'DO_AN' => 'Đồ án / Khóa luận',
            'BAI_TAP' => 'Bài tập & Bài giải',
            'KHAC' => 'Tài liệu khác',
        ];

        $categoryRows = $this->repository->getStorageByCategory();
        $byCategory = $categoryRows->map(function ($row) use ($categoryLabels, $totalDocKb) {
            $kb = (float) ($row->tong_dung_luong_kb ?? 0);
            $percent = $totalDocKb > 0 ? round(($kb / $totalDocKb) * 100, 2) : 0.0;

            return [
                'phan_loai' => $row->phan_loai,
                'ten_loai' => $categoryLabels[$row->phan_loai] ?? $row->phan_loai,
                'so_tai_lieu' => (int) $row->so_tai_lieu,
                'tong_dung_luong_kb' => round($kb, 2),
                'dung_luong_dinh_dang' => StorageMetric::formatKb($kb),
                'ty_le_phan_tram' => $percent,
            ];
        })->toArray();

        $extensionRows = $this->repository->getStorageByExtension(10);
        $byExtension = $extensionRows->map(function ($row) use ($totalDocKb) {
            $kb = (float) ($row->tong_dung_luong_kb ?? 0);
            $percent = $totalDocKb > 0 ? round(($kb / $totalDocKb) * 100, 2) : 0.0;
            $ext = strtolower($row->dinh_dang_tep ?? 'khác');

            return [
                'dinh_dang_tep' => $ext,
                'so_tai_lieu' => (int) $row->so_tai_lieu,
                'tong_dung_luong_kb' => round($kb, 2),
                'dung_luong_dinh_dang' => StorageMetric::formatKb($kb),
                'ty_le_phan_tram' => $percent,
            ];
        })->toArray();

        return [
            'theo_muc_dich' => $byCategory,
            'theo_dinh_dang' => $byExtension,
        ];
    }

    // 6. Dung lượng thùng rác
    public function calculateSoftDeletedStorageStats(): array
    {
        $breakdown = $this->repository->getTrashBreakdown();
        $retentionDays = (int) config('documents.trash_admin_delete_after_days', 60);

        return [
            'tong_so_tai_lieu' => $breakdown['total_count'],
            'tong_dung_luong_kb' => round($breakdown['total_kb'], 2),
            'tong_dung_luong_dinh_dang' => StorageMetric::formatKb($breakdown['total_kb']),
            'da_het_han' => [
                'so_luong' => $breakdown['expired_count'],
                'dung_luong_kb' => round($breakdown['expired_kb'], 2),
                'dung_luong_dinh_dang' => StorageMetric::formatKb($breakdown['expired_kb']),
                'co_the_xoa_ngay' => $breakdown['expired_count'] > 0,
            ],
            'trong_an_han' => [
                'so_luong' => $breakdown['grace_count'],
                'dung_luong_kb' => round($breakdown['grace_kb'], 2),
                'dung_luong_dinh_dang' => StorageMetric::formatKb($breakdown['grace_kb']),
            ],
            'thoi_han_luu_ngay' => $retentionDays,
        ];
    }

    // 7. Minh chứng Alumni KYC (Private storage, Zero PII leakage)
    public function calculateKycStorageStats(): array
    {
        $diskName = config('storage.kyc_disk', 'local');
        $kycDir = config('storage.kyc_path', 'private/kyc');

        $scan = $this->scanner->scanKycDirectory($diskName, $kycDir);
        $physicalTotalBytes = $scan['total_bytes'];
        $physicalFilesCount = $scan['files_count'];

        $dbBreakdown = $this->repository->getKycDbBreakdown();
        $totalDbCount = $dbBreakdown['total_db_count'];
        $physicalKb = StorageMetric::bytesToKb($physicalTotalBytes);

        $byStatus = [
            'CHO_DUYET' => ['so_luong' => 0, 'dung_luong_kb' => 0, 'dung_luong_dinh_dang' => '0 KB'],
            'DA_DUYET' => ['so_luong' => 0, 'dung_luong_kb' => 0, 'dung_luong_dinh_dang' => '0 KB'],
            'TU_CHOI' => ['so_luong' => 0, 'dung_luong_kb' => 0, 'dung_luong_dinh_dang' => '0 KB'],
        ];

        $avgSizeKb = $totalDbCount > 0 ? ($physicalKb / $totalDbCount) : 0;

        foreach ($dbBreakdown['rows'] as $row) {
            $count = (int) $row->so_luong;
            $statusKb = round($count * $avgSizeKb, 2);
            $byStatus[$row->trang_thai] = [
                'so_luong' => $count,
                'dung_luong_kb' => $statusKb,
                'dung_luong_dinh_dang' => StorageMetric::formatKb($statusKb),
                'moi_nhat' => $row->moi_nhat,
                'cu_nhat' => $row->cu_nhat,
            ];
        }

        return [
            'khu_vuc_bao_mat' => 'Thư mục Private (Chỉ truy cập qua Signed URL 30 phút)',
            'tong_so_tep' => max($physicalFilesCount, $totalDbCount),
            'tong_so_ho_so_csdl' => $totalDbCount,
            'tong_dung_luong_bytes' => $physicalTotalBytes,
            'tong_dung_luong_kb' => $physicalKb,
            'tong_dung_luong_dinh_dang' => StorageMetric::formatKb($physicalKb),
            'kich_thuoc_trung_binh_kb' => round($avgSizeKb, 2),
            'kich_thuoc_trung_binh_dinh_dang' => StorageMetric::formatKb($avgSizeKb),
            'theo_trang_thai' => $byStatus,
        ];
    }

    // 9. File trùng lặp (SHA-256 hash)
    public function calculateDuplicateFiles(): array
    {
        $hashResult = $this->repository->getDuplicateHashClusters();
        $formattedClusters = [];

        foreach ($hashResult['clusters'] as $cluster) {
            $formattedClusters[] = [
                'ma_bam_file' => $cluster['ma_bam_file'],
                'so_ban_sao' => $cluster['so_ban_sao'],
                'so_ban_trung_thua' => $cluster['so_ban_trung_thua'],
                'dung_luong_file_kb' => $cluster['single_size_kb'],
                'dung_luong_file_dinh_dang' => StorageMetric::formatKb($cluster['single_size_kb']),
                'dung_luong_lang_phi_kb' => round($cluster['wasted_kb'], 2),
                'dung_luong_lang_phi_dinh_dang' => StorageMetric::formatKb($cluster['wasted_kb']),
                'dinh_dang_tep' => $cluster['dinh_dang_tep'],
                'danh_sach_tai_lieu' => $cluster['docs']->map(fn($d) => [
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

        return [
            'tong_so_cum_trung_lap' => count($formattedClusters),
            'tong_so_ban_trung_thua' => $hashResult['total_copies'],
            'tong_dung_luong_lang_phi_kb' => round($hashResult['total_wasted_kb'], 2),
            'tong_dung_luong_lang_phi_dinh_dang' => StorageMetric::formatKb($hashResult['total_wasted_kb']),
            'cum_trung_lap' => $formattedClusters,
        ];
    }

    // 10. Dung lượng có thể giải phóng
    public function calculateReclaimableStorageStats(array $totalStats, array $trashStats, array $dupStats): array
    {
        $dupWastedKb = (float) $dupStats['tong_dung_luong_lang_phi_kb'];
        $expiredTrashKb = (float) $trashStats['da_het_han']['dung_luong_kb'];
        $allTrashKb = (float) $trashStats['tong_dung_luong_kb'];

        $immediateReclaimableKb = $expiredTrashKb + $dupWastedKb;
        $maxReclaimableKb = $allTrashKb + $dupWastedKb;
        $totalUsedKb = (float) $totalStats['tong_dung_luong_kb'];

        $immediatePercent = $totalUsedKb > 0 ? round(($immediateReclaimableKb / $totalUsedKb) * 100, 1) : 0.0;
        $maxPercent = $totalUsedKb > 0 ? round(($maxReclaimableKb / $totalUsedKb) * 100, 1) : 0.0;

        return [
            'giai_phong_ngay' => [
                'dung_luong_kb' => round($immediateReclaimableKb, 2),
                'dung_luong_dinh_dang' => StorageMetric::formatKb($immediateReclaimableKb),
                'ty_le_phan_tram' => $immediatePercent,
                'so_file_thung_rac_het_han' => $trashStats['da_het_han']['so_luong'],
                'so_file_trung_lap' => $dupStats['tong_so_ban_trung_thua'],
            ],
            'giai_phong_toi_da' => [
                'dung_luong_kb' => round($maxReclaimableKb, 2),
                'dung_luong_dinh_dang' => StorageMetric::formatKb($maxReclaimableKb),
                'ty_le_phan_tram' => $maxPercent,
                'so_file_thung_rac_tat_ca' => $trashStats['tong_so_tai_lieu'],
            ],
            'chi_tiet' => [
                'thung_rac_het_han_kb' => $expiredTrashKb,
                'thung_rac_het_han_dinh_dang' => StorageMetric::formatKb($expiredTrashKb),
                'thung_rac_tat_ca_kb' => $allTrashKb,
                'thung_rac_tat_ca_dinh_dang' => StorageMetric::formatKb($allTrashKb),
                'tep_trung_lap_kb' => $dupWastedKb,
                'tep_trung_lap_dinh_dang' => StorageMetric::formatKb($dupWastedKb),
            ],
        ];
    }

    // 11. Cảnh báo vượt ngưỡng
    public function calculateThresholdAlert(array $totalStats): array
    {
        $usedPercent = (float) $totalStats['ty_le_su_dung_thuc_te'];
        $warningPercent = (float) config('storage.warning_threshold_percent', 80);
        $criticalPercent = (float) config('storage.critical_threshold_percent', 90);

        return $this->evaluator->evaluateThresholdAlert(
            $usedPercent,
            $warningPercent,
            $criticalPercent,
            $totalStats['tong_dung_luong_dinh_dang'],
            $totalStats['han_muc_dinh_dang']
        );
    }

    // 12. Tốc độ tăng trưởng
    public function calculateGrowthRateStats(array $totalStats, int $months = 6): array
    {
        $startDate = Carbon::now()->subMonths($months - 1)->startOfMonth();
        $growthData = $this->repository->getGrowthTimelineData($startDate);
        $monthlyRecords = $growthData['records'];
        $cumulativeKb = $growthData['prior_cumulative_kb'];

        $timeline = [];
        $previousMonthKb = null;
        $allMonthlyKb = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $monthKey = Carbon::now()->subMonths($i)->format('Y-m');
            $record = $monthlyRecords->get($monthKey);

            $newFiles = $record ? (int) $record->so_file_moi : 0;
            $newKb = $record ? (float) $record->dung_luong_moi_kb : 0.0;

            $cumulativeKb += $newKb;
            $allMonthlyKb[] = $newKb;

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
                'dung_luong_moi_dinh_dang' => StorageMetric::formatKb($newKb),
                'dung_luong_tich_luy_kb' => round($cumulativeKb, 2),
                'dung_luong_tich_luy_dinh_dang' => StorageMetric::formatKb($cumulativeKb),
                'toc_do_tang_truong_phan_tram' => $growthRatePercent,
            ];

            $previousMonthKb = $newKb;
        }

        $avgMonthlyKb = count($allMonthlyKb) > 0 ? (array_sum($allMonthlyKb) / count($allMonthlyKb)) : 0.0;
        $avgDailyKb = $avgMonthlyKb / 30;

        $remainingKb = max(0, ($totalStats['han_muc_mb'] * 1024) - $totalStats['tong_dung_luong_kb']);
        $estimatedDaysRemaining = $avgDailyKb > 0 ? (int) round($remainingKb / $avgDailyKb) : null;

        return [
            'so_thang_thong_ke' => $months,
            'dong_thoi_gian' => $timeline,
            'toc_do_trung_binh_thang_kb' => round($avgMonthlyKb, 2),
            'toc_do_trung_binh_thang_dinh_dang' => StorageMetric::formatKb($avgMonthlyKb),
            'toc_do_trung_binh_ngay_kb' => round($avgDailyKb, 2),
            'toc_do_trung_binh_ngay_dinh_dang' => StorageMetric::formatKb($avgDailyKb),
            'du_bao_so_ngay_con_lai' => $estimatedDaysRemaining,
        ];
    }
}
