<?php

namespace App\Modules\Administration\Infrastructure\Persistence;

use App\Enums\CapQuanTri;
use App\Enums\VaiTroNguoiDung;
use App\Models\Khoa;
use App\Models\LichSuPhanQuyenNguoiDung;
use App\Models\LichSuTrangThaiNguoiDung;
use App\Models\NguoiDung;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class AdminUserRepository
{
    // Truy vấn danh sách người dùng có phân trang và bộ lọc tìm kiếm
    public function getPaginatedUsers(
        ?string $search = null,
        ?string $vaiTro = null,
        ?string $trangThai = null,
        ?int $maKhoa = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = NguoiDung::with(['khoa:ma_khoa,ten_khoa,ky_hieu_khoa', 'viDiem'])
            ->latest('ma_nguoi_dung');

        // Tìm kiếm theo từ khóa (họ tên, email, MSSV)
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('ho_ten', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('mssv', 'like', "%{$search}%");
            });
        }

        // Lọc theo vai trò người dùng
        if ($vaiTro) {
            $query->where('vai_tro', $vaiTro);
        }

        // Lọc theo trạng thái hoạt động
        if ($trangThai) {
            $query->where('trang_thai', $trangThai);
        }

        // Lọc theo khoa
        if ($maKhoa) {
            $query->where('ma_khoa', $maKhoa);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    // Lấy thông tin chi tiết một người dùng kèm khoa và ví điểm
    public function findUserById(int $maNguoiDung): NguoiDung
    {
        return NguoiDung::with(['khoa', 'viDiem'])->findOrFail($maNguoiDung);
    }

    // Lấy lịch sử thay đổi trạng thái của người dùng
    public function getUserStatusHistory(int $maNguoiDung): Collection
    {
        return LichSuTrangThaiNguoiDung::with('nguoiThucHien:ma_nguoi_dung,ho_ten')
            ->where('ma_nguoi_dung', $maNguoiDung)
            ->latest()
            ->get();
    }

    // Lấy lịch sử thay đổi vai trò phân quyền của người dùng
    public function getUserRoleHistory(int $maNguoiDung): Collection
    {
        return LichSuPhanQuyenNguoiDung::with('nguoiThucHien:ma_nguoi_dung,ho_ten')
            ->where('ma_nguoi_dung', $maNguoiDung)
            ->latest()
            ->get();
    }

    // Lấy danh sách tất cả các khoa để hiển thị lên bộ lọc
    public function getAllFaculties(): Collection
    {
        return Khoa::select('ma_khoa', 'ten_khoa', 'ky_hieu_khoa')->get();
    }

    // Đếm tổng số lượng Super Admin hiện có trong hệ thống
    public function countSuperAdmins(): int
    {
        return NguoiDung::where('vai_tro', VaiTroNguoiDung::QUAN_TRI_VIEN)
            ->where('cap_quan_tri', CapQuanTri::TOI_CAO)
            ->count();
    }
}

