<?php

namespace App\Modules\Moderation\Infrastructure\Persistence;

use App\Models\YeuCauGoBoTaiLieu;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RemovalRequestRepository
{
    // Phân trang danh sách yêu cầu gỡ bỏ tài liệu
    public function paginateRemovalRequests(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return YeuCauGoBoTaiLieu::with([
            'taiLieu' => fn ($query) => $query->withTrashed()->with(['monHoc', 'nguoiDang']),
            'nguoiYeuCau',
            'nguoiXuLy',
        ])
            ->when(!empty($filters['status']), fn($query) => $query->where('trang_thai', $filters['status']))
            ->latest('ma_yeu_cau_go_bo')
            ->paginate($perPage)
            ->withQueryString();
    }

    // Tìm yêu cầu gỡ bỏ theo ID kèm quan hệ
    public function findRemovalRequestById(int $id): YeuCauGoBoTaiLieu
    {
        return YeuCauGoBoTaiLieu::with([
            'taiLieu.monHoc',
            'taiLieu.nguoiDang',
            'nguoiYeuCau',
            'nguoiXuLy',
        ])->findOrFail($id);
    }

    // Khóa yêu cầu gỡ bỏ trong Transaction (Pessimistic Locking)
    public function findRemovalRequestForUpdate(int $id): YeuCauGoBoTaiLieu
    {
        return YeuCauGoBoTaiLieu::query()
            ->with(['taiLieu', 'nguoiYeuCau'])
            ->lockForUpdate()
            ->findOrFail($id);
    }
}
