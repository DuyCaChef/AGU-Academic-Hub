<?php

namespace App\Modules\Administration\Application\Queries;

use App\Models\TaiLieu;
use App\Modules\Administration\Domain\Services\StorageThresholdEvaluator;
use App\Modules\Administration\Domain\ValueObjects\StorageMetric;
use App\Modules\Administration\Infrastructure\Persistence\StorageStatsRepository;

class GetUnusuallyLargeFilesQuery
{
    public function __construct(
        protected StorageStatsRepository $repository,
        protected StorageThresholdEvaluator $evaluator
    ) {}

    // Lấy danh sách file có dung lượng lớn bất thường theo ngưỡng tùy chỉnh
    public function execute(?int $thresholdKb = null, int $limit = 50): array
    {
        $threshold = $thresholdKb ?? (int) config('storage.large_file_threshold_kb', 20480);
        $largeFiles = $this->repository->getUnusuallyLargeFiles($threshold, $limit);

        return [
            'nguong_kb' => $threshold,
            'nguong_dinh_dang' => StorageMetric::formatKb($threshold),
            'tong_so_tep_lon' => $largeFiles->count(),
            'tong_dung_luong_tep_lon_kb' => round((float) $largeFiles->sum('dung_luong_kb'), 2),
            'tong_dung_luong_tep_lon_dinh_dang' => StorageMetric::formatKb((float) $largeFiles->sum('dung_luong_kb')),
            'danh_sach' => $largeFiles->map(function (TaiLieu $doc) {
                $isTrashed = $doc->trashed();
                $sizeKb = (float) $doc->dung_luong_kb;
                $severity = $this->evaluator->evaluateFileSeverity($sizeKb);

                return [
                    'ma_tai_lieu' => $doc->ma_tai_lieu,
                    'tieu_de' => $doc->tieu_de,
                    'dinh_dang_tep' => strtolower($doc->dinh_dang_tep),
                    'dung_luong_kb' => $sizeKb,
                    'dung_luong_dinh_dang' => StorageMetric::formatKb($sizeKb),
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
}
