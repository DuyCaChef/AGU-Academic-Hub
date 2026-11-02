<?php

namespace App\Modules\Administration\Infrastructure\Persistence;

use App\Models\TaiLieu;
use App\Models\YeuCauXacThuc;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StorageStatsRepository
{
    // Lấy số lượng và tổng dung lượng tài liệu đang hoạt động (Active)
    public function getActiveDocumentsStats(): array
    {
        $count = (int) TaiLieu::query()->whereNull('ngay_xoa_tam')->count();
        $kb = (float) (TaiLieu::query()->whereNull('ngay_xoa_tam')->sum('dung_luong_kb') ?? 0);

        return ['count' => $count, 'kb' => $kb];
    }

    // Lấy số lượng và tổng dung lượng tài liệu đã xóa mềm (Trashed)
    public function getTrashedDocumentsStats(): array
    {
        $count = (int) TaiLieu::onlyTrashed()->count();
        $kb = (float) (TaiLieu::onlyTrashed()->sum('dung_luong_kb') ?? 0);

        return ['count' => $count, 'kb' => $kb];
    }

    // Tổng dung lượng của tất cả tài liệu (bao gồm cả active và soft-deleted) để làm mẫu số tính tỷ lệ phần trăm
    public function getTotalAllDocumentsKb(): float
    {
        return (float) (TaiLieu::withTrashed()->sum('dung_luong_kb') ?? 0);
    }

    // Thống kê dung lượng gom nhóm theo Khoa (tương thích MySQL 8.0 ONLY_FULL_GROUP_BY)
    public function getStorageByFaculty(): Collection
    {
        return DB::table('tai_lieu')
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
    }

    // Thống kê dung lượng gom nhóm theo Môn học (hỗ trợ tìm kiếm theo mã/tên/khoa)
    public function getStorageByCourse(int $limit = 20, ?string $search = null): Collection
    {
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

        return $query->limit($limit)->get();
    }

    // Thống kê dung lượng gom nhóm theo phân loại mục đích học tập
    public function getStorageByCategory(): Collection
    {
        return DB::table('tai_lieu')
            ->select([
                'phan_loai',
                DB::raw('COUNT(ma_tai_lieu) as so_tai_lieu'),
                DB::raw('SUM(dung_luong_kb) as tong_dung_luong_kb'),
            ])
            ->groupBy('phan_loai')
            ->orderByDesc('tong_dung_luong_kb')
            ->get();
    }

    // Thống kê dung lượng gom nhóm theo định dạng tệp mở rộng
    public function getStorageByExtension(int $limit = 10): Collection
    {
        return DB::table('tai_lieu')
            ->select([
                'dinh_dang_tep',
                DB::raw('COUNT(ma_tai_lieu) as so_tai_lieu'),
                DB::raw('SUM(dung_luong_kb) as tong_dung_luong_kb'),
            ])
            ->groupBy('dinh_dang_tep')
            ->orderByDesc('tong_dung_luong_kb')
            ->limit($limit)
            ->get();
    }

    // Thống kê dung lượng gom nhóm theo người đăng (Top Uploaders)
    public function getStorageByUploader(int $limit = 20, ?string $search = null): Collection
    {
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

        return $query->limit($limit)->get();
    }

    // Phân tích chi tiết dung lượng trong thùng rác: Hết hạn vs Trong thời gian ân hạn
    public function getTrashBreakdown(): array
    {
        $trashedQuery = TaiLieu::onlyTrashed();

        $totalCount = (int) (clone $trashedQuery)->count();
        $totalKb = (float) ((clone $trashedQuery)->sum('dung_luong_kb') ?? 0);

        $now = now();
        $expiredQuery = (clone $trashedQuery)->where('han_luu_thung_rac', '<=', $now);
        $expiredCount = (int) (clone $expiredQuery)->count();
        $expiredKb = (float) ((clone $expiredQuery)->sum('dung_luong_kb') ?? 0);

        $graceQuery = (clone $trashedQuery)->where(function ($q) use ($now) {
            $q->where('han_luu_thung_rac', '>', $now)
                ->orWhereNull('han_luu_thung_rac');
        });
        $graceCount = (int) (clone $graceQuery)->count();
        $graceKb = (float) ((clone $graceQuery)->sum('dung_luong_kb') ?? 0);

        return [
            'total_count' => $totalCount,
            'total_kb' => $totalKb,
            'expired_count' => $expiredCount,
            'expired_kb' => $expiredKb,
            'grace_count' => $graceCount,
            'grace_kb' => $graceKb,
        ];
    }

    // Lấy thống kê hồ sơ xác thực KYC từ CSDL
    public function getKycDbBreakdown(): array
    {
        $rows = DB::table('yeu_cau_xac_thuc')
            ->select([
                'trang_thai',
                DB::raw('COUNT(ma_yeu_cau_xac_thuc) as so_luong'),
                DB::raw('MAX(created_at) as moi_nhat'),
                DB::raw('MIN(created_at) as cu_nhat'),
            ])
            ->groupBy('trang_thai')
            ->get();

        $totalDbCount = (int) YeuCauXacThuc::query()->count();

        return [
            'total_db_count' => $totalDbCount,
            'rows' => $rows,
        ];
    }

    // Lấy danh sách các tài liệu có dung lượng lớn bất thường
    public function getUnusuallyLargeFiles(int $thresholdKb, int $limit = 50): Collection
    {
        return TaiLieu::withTrashed()
            ->with(['monHoc.khoa', 'nguoiDang'])
            ->where('dung_luong_kb', '>=', $thresholdKb)
            ->orderByDesc('dung_luong_kb')
            ->limit($limit)
            ->get();
    }

    // Nhóm và phát hiện các cụm tệp trùng lặp theo mã băm SHA-256
    public function getDuplicateHashClusters(): array
    {
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
                'single_size_kb' => $singleFileSizeKb,
                'wasted_kb' => $wastedSpaceKb,
                'dinh_dang_tep' => strtolower($firstDoc->dinh_dang_tep),
                'docs' => $docs,
            ];
        }

        // Sắp xếp theo dung lượng lãng phí giảm dần
        usort($clusters, fn($a, $b) => $b['wasted_kb'] <=> $a['wasted_kb']);

        return [
            'clusters' => $clusters,
            'total_copies' => $totalDuplicateCopies,
            'total_wasted_kb' => $totalWastedKb,
        ];
    }

    // Lấy số liệu tăng trưởng dung lượng theo tháng (phân nhánh MySQL vs SQLite)
    public function getGrowthTimelineData(Carbon $startDate): array
    {
        $driver = DB::connection()->getDriverName();
        $dateExpr = $driver === 'sqlite'
            ? "strftime('%Y-%m', created_at)"
            : "DATE_FORMAT(created_at, '%Y-%m')";

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

        $priorCumulativeKb = (float) (TaiLieu::withTrashed()
            ->where('created_at', '<', $startDate)
            ->sum('dung_luong_kb') ?? 0);

        return [
            'records' => $monthlyRecords,
            'prior_cumulative_kb' => $priorCumulativeKb,
        ];
    }
}
