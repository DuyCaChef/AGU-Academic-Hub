<?php

namespace App\Modules\Administration\Application\Queries;

use App\Enums\VaiTroNguoiDung;
use App\Models\LichSuPhanQuyenNguoiDung;
use App\Models\LichSuTrangThaiNguoiDung;
use App\Models\NguoiDung;
use App\Modules\Administration\Domain\Services\AdminPermissionRuleEvaluator;
use App\Modules\Administration\Infrastructure\Persistence\AdminUserRepository;

class GetUserDetailQuery
{
    public function __construct(
        protected AdminUserRepository $userRepository,
        protected AdminPermissionRuleEvaluator $evaluator
    ) {}

    // Lấy thông tin chi tiết một tài khoản kèm toàn bộ lịch sử trạng thái và lịch sử phân quyền
    public function execute(int $maNguoiDung, NguoiDung $actor): array
    {
        $user = $this->userRepository->findUserById($maNguoiDung);

        $statusHistory = $this->userRepository->getUserStatusHistory($maNguoiDung)
            ->map(fn(LichSuTrangThaiNguoiDung $item) => [
                'trang_thai_cu' => $item->trang_thai_cu,
                'trang_thai_moi' => $item->trang_thai_moi,
                'hanh_dong' => $item->hanh_dong,
                'ly_do' => $item->ly_do,
                'nguoi_thuc_hien' => $item->nguoiThucHien?->ho_ten,
                'thoi_diem' => $item->created_at?->format('d/m/Y H:i'),
            ]);

        $roleHistory = $this->userRepository->getUserRoleHistory($maNguoiDung)
            ->map(fn(LichSuPhanQuyenNguoiDung $item) => [
                'vai_tro_cu' => $item->vai_tro_cu,
                'vai_tro_moi' => $item->vai_tro_moi,
                'cap_quan_tri_cu' => $item->cap_quan_tri_cu,
                'cap_quan_tri_moi' => $item->cap_quan_tri_moi,
                'ma_khoa_cu' => $item->ma_khoa_cu,
                'ma_khoa_moi' => $item->ma_khoa_moi,
                'ly_do' => $item->ly_do,
                'nguoi_thuc_hien' => $item->nguoiThucHien?->ho_ten,
                'thoi_diem' => $item->created_at?->format('d/m/Y H:i'),
            ]);

        $isStudentOrAlumni = in_array($user->vai_tro, [VaiTroNguoiDung::SINH_VIEN, VaiTroNguoiDung::CUU_SINH_VIEN], true);

        return [
            'user' => [
                'ma_nguoi_dung' => $user->ma_nguoi_dung,
                'ho_ten' => $user->ho_ten,
                'email' => $user->email,
                'mssv' => $user->mssv,
                'vai_tro' => $user->vai_tro?->value,
                'ten_vai_tro' => $user->vai_tro?->label(),
                'cap_quan_tri' => $user->cap_quan_tri?->value,
                'ten_cap_quan_tri' => $user->cap_quan_tri?->label(),
                'trang_thai' => $user->trang_thai?->value,
                'ten_trang_thai' => $user->trang_thai?->label(),
                'ly_do_khoa' => $user->ly_do_khoa,
                'so_lan_vi_pham' => $user->so_lan_vi_pham,
                'canh_bao_vi_pham' => $user->so_lan_vi_pham > 3
                    ? 'Tài khoản này đã vi phạm trên 3 lần. Khuyến nghị Admin xem xét khóa tài khoản.'
                    : null,
                'muc_canh_bao' => $user->muc_canh_bao,
                'diem_uy_tin' => $user->diem_uy_tin,
                'nam_tot_nghiep' => $user->nam_tot_nghiep,
                'ngay_tao' => $user->created_at?->format('d/m/Y H:i'),
                'khoa' => $isStudentOrAlumni && $user->khoa ? [
                    'ma_khoa' => $user->khoa->ma_khoa,
                    'ten_khoa' => $user->khoa->ten_khoa,
                ] : null,
                'vi_diem' => $user->viDiem ? [
                    'diem_kha_dung' => $user->viDiem->diem_kha_dung,
                    'diem_tam_giu' => $user->viDiem->diem_tam_giu,
                ] : null,
            ],
            'permissions' => [
                'can_manage_target' => $this->evaluator->canManageTarget($actor, $user),
                'can_manage_admin' => $actor->laSuperAdmin(),
            ],
            'faculties' => $this->userRepository->getAllFaculties(),
            'statusHistory' => $statusHistory,
            'roleHistory' => $roleHistory,
        ];
    }
}

