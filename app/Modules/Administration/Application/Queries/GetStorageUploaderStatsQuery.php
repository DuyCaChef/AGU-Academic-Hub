<?php

namespace App\Modules\Administration\Application\Queries;

use App\Modules\Administration\Domain\ValueObjects\StorageMetric;
use App\Modules\Administration\Infrastructure\Persistence\StorageStatsRepository;

class GetStorageUploaderStatsQuery
{
    public function __construct(
        protected StorageStatsRepository $repository
    ) {}

    // Thống kê dung lượng theo người đăng kèm tìm kiếm
    public function execute(int $limit = 20, ?string $search = null): array
    {
        $totalDocKb = $this->repository->getTotalAllDocumentsKb();
        $uploaders = $this->repository->getStorageByUploader($limit, $search);

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
                'dung_luong_dinh_dang' => StorageMetric::formatKb($kb),
                'ty_le_phan_tram' => $percent,
            ];
        })->toArray();
    }
}
