<?php

namespace App\Modules\Documents\Infrastructure\Persistence;

use App\Models\TaiLieu;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DocumentRepository
{
    // Tìm kiếm và phân trang danh sách tài liệu công khai
    public function paginatePublicDocuments(array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        $query = TaiLieu::query()
            ->with(['nguoiDang:ma_nguoi_dung,ho_ten', 'monHoc:ma_mon_hoc,ten_mon_hoc,ma_hoc_phan'])
            ->where('trang_thai', 'DA_DUYET')
            ->when(!empty($filters['search']), function ($query) use ($filters): void {
                $search = $filters['search'];
                $query->where('tieu_de', 'like', "%{$search}%");
            })
            ->when(!empty($filters['category']) && $filters['category'] !== 'ALL', function ($query) use ($filters): void {
                $query->where('phan_loai', $filters['category']);
            })
            ->when(!empty($filters['course_id']), function ($query) use ($filters): void {
                $query->where('ma_mon_hoc', $filters['course_id']);
            });

        $user = auth()->user();
        if ($user) {
            $userId = $user->ma_nguoi_dung ?? $user->getKey();
            $query->withExists(['binhChons as da_thich' => function ($q) use ($userId): void {
                $q->where('ma_nguoi_dung', $userId);
            }]);
        }

        $sort = $filters['sort'] ?? 'latest';
        match ($sort) {
            'downloads' => $query->orderByDesc('luot_tai'),
            'upvotes' => $query->orderByDesc('luot_thich'),
            default => $query->latest('ma_tai_lieu'),
        };

        return $query->paginate($perPage)->withQueryString();
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

