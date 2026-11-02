<?php

namespace App\Modules\Documents\Infrastructure\Persistence;

use App\Models\TaiLieu;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DocumentRepository
{
    // Tìm kiếm và phân trang danh sách tài liệu công khai
    public function paginatePublicDocuments(array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        return TaiLieu::query()
            ->with(['nguoiDang:ma_nguoi_dung,ho_ten', 'monHoc:ma_mon_hoc,ten_mon_hoc,ma_hoc_phan'])
            ->where('trang_thai', 'DA_DUYET')
            ->when(!empty($filters['search']), function ($query) use ($filters): void {
                $search = $filters['search'];
                $query->where(function ($sub) use ($search): void {
                    $sub->where('tieu_de', 'like', "%{$search}%")
                        ->orWhere('mo_ta', 'like', "%{$search}%");
                });
            })
            ->when(!empty($filters['category']), function ($query) use ($filters): void {
                $query->where('phan_loai', $filters['category']);
            })
            ->when(!empty($filters['course_id']), function ($query) use ($filters): void {
                $query->where('ma_mon_hoc', $filters['course_id']);
            })
            ->latest('ma_tai_lieu')
            ->paginate($perPage)
            ->withQueryString();
    }

    // Tìm tài liệu theo ID (kèm người đăng và môn học)
    public function findById(int $id, bool $withTrashed = false): TaiLieu
    {
        $query = $withTrashed ? TaiLieu::withTrashed() : TaiLieu::query();

        return $query->with(['nguoiDang', 'monHoc', 'phienBans', 'taiLieuGoc'])->findOrFail($id);
    }

    // Tăng lượt tải của tài liệu
    public function incrementDownloads(TaiLieu $document): void
    {
        $document->increment('luot_tai');
    }
}

