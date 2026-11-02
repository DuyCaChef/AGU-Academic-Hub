<?php

namespace App\Modules\Administration\Application\Queries;

use App\Modules\Administration\Domain\ValueObjects\StorageMetric;
use App\Modules\Administration\Infrastructure\Persistence\StorageStatsRepository;

class GetStorageCourseStatsQuery
{
    public function __construct(
        protected StorageStatsRepository $repository
    ) {}

    // Thống kê dung lượng theo môn học kèm tìm kiếm
    public function execute(int $limit = 20, ?string $search = null): array
    {
        $totalDocKb = $this->repository->getTotalAllDocumentsKb();
        $courses = $this->repository->getStorageByCourse($limit, $search);

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
                'dung_luong_dinh_dang' => StorageMetric::formatKb($kb),
                'tai_lieu_moi_nhat' => $row->tai_lieu_moi_nhat,
                'ty_le_phan_tram' => $percent,
            ];
        })->toArray();
    }
}
