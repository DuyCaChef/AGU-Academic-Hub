<?php

namespace App\Modules\Moderation\Infrastructure\Persistence;

use App\Models\BaoCaoViPham;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ModerationReportRepository
{
    // Phân trang danh sách báo cáo vi phạm kèm bộ lọc
    public function paginateReports(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return BaoCaoViPham::with(['taiLieu', 'nguoiBaoCao'])
            ->when(!empty($filters['status']), fn($query) => $query->where('trang_thai_xu_ly', $filters['status']))
            ->when(!empty($filters['reason']), fn($query) => $query->where('ly_do', $filters['reason']))
            ->when(!empty($filters['from']), fn($query) => $query->whereDate('created_at', '>=', $filters['from']))
            ->when(!empty($filters['to']), fn($query) => $query->whereDate('created_at', '<=', $filters['to']))
            ->latest('ma_bao_cao')
            ->paginate($perPage)
            ->withQueryString();
    }

    // Tìm báo cáo vi phạm theo ID kèm quan hệ
    public function findReportById(int $id): BaoCaoViPham
    {
        return BaoCaoViPham::with([
            'taiLieu' => fn($query) => $query->withTrashed()->with('nguoiDang'),
            'nguoiBaoCao',
            'nguoiXuLy',
        ])->findOrFail($id);
    }

    // Lấy danh sách các báo cáo liên quan cùng một tài liệu
    public function getRelatedReports(int $documentId, int $excludeReportId): \Illuminate\Database\Eloquent\Collection
    {
        return BaoCaoViPham::with(['nguoiBaoCao', 'nguoiXuLy'])
            ->where('ma_tai_lieu', $documentId)
            ->where('ma_bao_cao', '!=', $excludeReportId)
            ->latest('ma_bao_cao')
            ->get();
    }
}

