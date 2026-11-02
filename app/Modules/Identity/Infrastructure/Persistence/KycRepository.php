<?php

namespace App\Modules\Identity\Infrastructure\Persistence;

use App\Enums\LoaiXacThuc;
use App\Enums\TrangThaiXacThuc;
use App\Enums\VaiTroNguoiDung;
use App\Models\YeuCauXacThuc;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class KycRepository
{
    // Lấy danh sách hồ sơ KYC cựu sinh viên đang chờ duyệt kèm phân trang
    public function getPendingAlumniKycPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return YeuCauXacThuc::with([
            'nguoiDung:ma_nguoi_dung,ma_khoa,ho_ten,email,mssv,nam_tot_nghiep',
            'nguoiDung.khoa:ma_khoa,ten_khoa,ky_hieu_khoa',
        ])
            ->where('loai_xac_thuc', LoaiXacThuc::CUU_SINH_VIEN)
            ->where('trang_thai', TrangThaiXacThuc::CHO_DUYET)
            ->whereHas('nguoiDung', function ($query): void {
                $query->where('vai_tro', VaiTroNguoiDung::CUU_SINH_VIEN)
                    ->whereNotExists(function ($subQuery): void {
                        $subQuery->selectRaw('1')
                            ->from('danh_sach_sinh_vien_raw')
                            ->whereColumn('danh_sach_sinh_vien_raw.mssv', 'nguoi_dung.mssv');
                    });
            })
            ->orderBy('ma_yeu_cau_xac_thuc', 'asc')
            ->paginate($perPage);
    }

    // Tìm hồ sơ KYC cựu sinh viên theo ID
    public function findAlumniKycById(int $id): YeuCauXacThuc
    {
        return YeuCauXacThuc::with('nguoiDung')
            ->where('loai_xac_thuc', LoaiXacThuc::CUU_SINH_VIEN)
            ->findOrFail($id);
    }

    // Tìm hồ sơ KYC cựu sinh viên theo ID và khóa hàng (Pessimistic Locking) trong Transaction
    public function findAlumniKycForUpdate(int $id): YeuCauXacThuc
    {
        return YeuCauXacThuc::with('nguoiDung')
            ->where('loai_xac_thuc', LoaiXacThuc::CUU_SINH_VIEN)
            ->whereKey($id)
            ->lockForUpdate()
            ->firstOrFail();
    }
}

