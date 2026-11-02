<?php

namespace App\Modules\Moderation\Infrastructure\Persistence;

use App\Models\TaiLieu;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TrashManagementRepository
{
    // Phân trang danh sách tài liệu trong thùng rác và tính toán điều kiện xóa vĩnh viễn
    public function paginateDeletedDocuments(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $paginator = TaiLieu::onlyTrashed()
            ->with(['nguoiDang', 'nguoiXoa', 'monHoc', 'baoCaoViPhams'])
            ->when(!empty($filters['from']), fn($query) => $query->whereDate('ngay_xoa_tam', '>=', $filters['from']))
            ->when(!empty($filters['to']), fn($query) => $query->whereDate('ngay_xoa_tam', '<=', $filters['to']))
            ->when(!empty($filters['deleted_by']), fn($query) => $query->where('ma_nguoi_xoa', (int) $filters['deleted_by']))
            ->when(!empty($filters['source']), fn($query) => $query->where('nguon_xoa', $filters['source']))
            ->when(!empty($filters['status']), fn($query) => $query->where('trang_thai', $filters['status']))
            ->latest('ngay_xoa_tam')
            ->paginate($perPage)
            ->withQueryString();

        $adminDays = config('documents.trash_admin_delete_after_days', 60);

        $paginator->getCollection()->each(function (TaiLieu $document) use ($adminDays): void {
            $eligibleAt = $document->han_luu_thung_rac
                ?? $document->ngay_xoa_tam?->copy()->addDays($adminDays);

            $document->setAttribute('co_the_xoa_vinh_vien', $eligibleAt?->isPast() ?? false);
            $document->setAttribute('thoi_diem_xoa_vinh_vien_thu_cong', $eligibleAt);
        });

        return $paginator;
    }

    // Tìm tài liệu đã xóa mềm với khóa bi quan (Lock For Update)
    public function findTrashedForUpdate(int $id): TaiLieu
    {
        return TaiLieu::onlyTrashed()
            ->with(['taiLieuGoc', 'phienBans'])
            ->lockForUpdate()
            ->findOrFail($id);
    }
}

