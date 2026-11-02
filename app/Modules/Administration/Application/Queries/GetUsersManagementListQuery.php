<?php

namespace App\Modules\Administration\Application\Queries;

use App\Enums\VaiTroNguoiDung;
use App\Models\NguoiDung;
use App\Modules\Administration\Infrastructure\Persistence\AdminUserRepository;

class GetUsersManagementListQuery
{
    public function __construct(
        protected AdminUserRepository $userRepository
    ) {}

    // Lấy danh sách người dùng phân trang kèm bộ lọc và danh mục khoa
    public function execute(array $filters): array
    {
        $search = $filters['search'] ?? null;
        $vaiTro = $filters['vai_tro'] ?? null;
        $trangThai = $filters['trang_thai'] ?? null;
        $maKhoa = !empty($filters['ma_khoa']) ? (int) $filters['ma_khoa'] : null;

        $paginated = $this->userRepository->getPaginatedUsers($search, $vaiTro, $trangThai, $maKhoa, 15);

        // Chuẩn hóa dữ liệu từng người dùng tương thích giao diện React
        $users = $paginated->through(function (NguoiDung $u) {
            $isStudentOrAlumni = in_array($u->vai_tro, [VaiTroNguoiDung::SINH_VIEN, VaiTroNguoiDung::CUU_SINH_VIEN], true);

            return [
                'ma_nguoi_dung' => $u->ma_nguoi_dung,
                'ho_ten' => $u->ho_ten,
                'email' => $u->email,
                'mssv' => $u->mssv,
                'vai_tro' => $u->vai_tro?->value,
                'ten_vai_tro' => $u->vai_tro?->label(),
                'cap_quan_tri' => $u->cap_quan_tri?->value,
                'ten_cap_quan_tri' => $u->cap_quan_tri?->label(),
                'trang_thai' => $u->trang_thai?->value,
                'ten_trang_thai' => $u->trang_thai?->label(),
                'ly_do_khoa' => $u->ly_do_khoa,
                'so_lan_vi_pham' => $u->so_lan_vi_pham,
                'muc_canh_bao' => $u->muc_canh_bao,
                'diem_uy_tin' => $u->diem_uy_tin,
                'ngay_tao' => $u->created_at?->format('d/m/Y'),
                'khoa' => $isStudentOrAlumni && $u->khoa ? [
                    'ma_khoa' => $u->khoa->ma_khoa,
                    'ten_khoa' => $u->khoa->ten_khoa,
                    'ky_hieu_khoa' => $u->khoa->ky_hieu_khoa,
                ] : null,
                'vi_diem' => $u->viDiem ? [
                    'diem_kha_dung' => $u->viDiem->diem_kha_dung,
                    'diem_tam_giu' => $u->viDiem->diem_tam_giu,
                ] : ['diem_kha_dung' => 0, 'diem_tam_giu' => 0],
            ];
        });

        return [
            'users' => $users,
            'filters' => [
                'search' => $search,
                'vai_tro' => $vaiTro,
                'trang_thai' => $trangThai,
                'ma_khoa' => $maKhoa ? (string) $maKhoa : null,
            ],
            'faculties' => $this->userRepository->getAllFaculties(),
        ];
    }
}

