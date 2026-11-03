<?php

namespace App\Modules\Administration\Infrastructure\Persistence;

use App\Models\NhatKyKiemToan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class AuditLogRepository
{
    /**
     * Lấy danh sách nhật ký kiểm toán có phân trang và áp dụng bộ lọc đa tiêu chí.
     */
    public function getPaginated(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = NhatKyKiemToan::query()
            ->with(['nguoiThucHien:ma_nguoi_dung,ho_ten,email,vai_tro,cap_quan_tri'])
            ->orderByDesc('id');

        $this->applyFilters($query, $filters);

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Thống kê tổng hợp số liệu kiểm toán phục vụ Dashboard / Header.
     */
    public function getSummaryStats(): array
    {
        $today = Carbon::today();

        return [
            'tong_so_nhat_ky'    => NhatKyKiemToan::query()->count(),
            'so_nhat_ky_hom_nay' => NhatKyKiemToan::query()->where('created_at', '>=', $today)->count(),
            'so_nguoi_thuc_hien' => NhatKyKiemToan::query()->whereNotNull('ma_nguoi_thuc_hien')->distinct('ma_nguoi_thuc_hien')->count('ma_nguoi_thuc_hien'),
            'so_hanh_dong_khac_nhau' => NhatKyKiemToan::query()->distinct('hanh_dong')->count('hanh_dong'),
        ];
    }

    /**
     * Tìm chi tiết 1 bản ghi kiểm toán kèm người thực hiện.
     */
    public function findById(int $id): ?NhatKyKiemToan
    {
        return NhatKyKiemToan::query()
            ->with(['nguoiThucHien:ma_nguoi_dung,ho_ten,email,vai_tro,cap_quan_tri'])
            ->find($id);
    }

    /**
     * Áp dụng các điều kiện lọc linh hoạt.
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        // 1. Tìm kiếm theo từ khóa (Mô tả, mã đối tượng, họ tên hoặc email người thực hiện)
        if (!empty($filters['search'])) {
            $keyword = trim($filters['search']);
            $query->where(function (Builder $q) use ($keyword) {
                $q->where('mo_ta', 'like', "%{$keyword}%")
                    ->orWhere('ma_doi_tuong', 'like', "%{$keyword}%")
                    ->orWhere('dia_chi_ip', 'like', "%{$keyword}%")
                    ->orWhereHas('nguoiThucHien', function (Builder $userQuery) use ($keyword) {
                        $userQuery->where('ho_ten', 'like', "%{$keyword}%")
                            ->orWhere('email', 'like', "%{$keyword}%");
                    });
            });
        }

        // 2. Lọc theo mã hành động nghiệp vụ
        if (!empty($filters['hanh_dong'])) {
            $query->where('hanh_dong', $filters['hanh_dong']);
        }

        // 3. Lọc theo loại đối tượng bị tác động
        if (!empty($filters['loai_doi_tuong'])) {
            $query->where('loai_doi_tuong', $filters['loai_doi_tuong']);
        }

        // 4. Lọc theo người thực hiện
        if (!empty($filters['ma_nguoi_thuc_hien'])) {
            $query->where('ma_nguoi_thuc_hien', $filters['ma_nguoi_thuc_hien']);
        }

        // 5. Lọc theo khoảng thời gian
        if (!empty($filters['tu_ngay'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['tu_ngay'])->startOfDay());
        }

        if (!empty($filters['den_ngay'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['den_ngay'])->endOfDay());
        }
    }
}

