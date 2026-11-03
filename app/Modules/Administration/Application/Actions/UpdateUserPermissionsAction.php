<?php

namespace App\Modules\Administration\Application\Actions;

use App\Enums\CapQuanTri;
use App\Enums\VaiTroNguoiDung;
use App\Models\LichSuPhanQuyenNguoiDung;
use App\Models\NguoiDung;
use App\Modules\Administration\Domain\Enums\HanhDongKiemToan;
use App\Modules\Administration\Domain\Enums\LoaiDoiTuongKiemToan;
use App\Modules\Administration\Domain\Services\AdminPermissionRuleEvaluator;
use App\Modules\Administration\Domain\Services\AuditLogger;
use App\Modules\Administration\Infrastructure\Persistence\AdminUserRepository;
use Illuminate\Support\Facades\DB;

class UpdateUserPermissionsAction
{
    public function __construct(
        protected AdminUserRepository $userRepository,
        protected AdminPermissionRuleEvaluator $evaluator
    ) {}

    // Cập nhật vai trò phân quyền (RBAC) và khoa cho người dùng trong transaction an toàn
    public function execute(NguoiDung $actor, int $maNguoiDung, array $data): array
    {
        $user = $this->userRepository->findUserById($maNguoiDung);

        // Kiểm tra ràng buộc: Kiểm duyệt viên và Quản trị viên không được gán khoa
        if (in_array($data['vai_tro'], ['KIEM_DUYET_VIEN', 'QUAN_TRI_VIEN'], true) && filled($data['ma_khoa'] ?? null)) {
            return [
                'success' => false,
                'type' => 'validation_error',
                'field' => 'ma_khoa',
                'message' => 'Tài khoản Kiểm duyệt viên và Quản trị viên không được gán Khoa.',
            ];
        }

        $role = VaiTroNguoiDung::from($data['vai_tro']);
        $currentSuperAdminCount = $this->userRepository->countSuperAdmins();

        // Đánh giá các quy tắc bảo mật phân quyền thông qua Domain Evaluator
        $validationError = $this->evaluator->validatePermissionUpdate(
            $actor,
            $user,
            $role,
            $data['cap_quan_tri'] ?? null,
            $data['ma_khoa'] ?? null,
            $currentSuperAdminCount
        );

        if ($validationError !== null) {
            return [
                'success' => false,
                'type' => 'error',
                'message' => $validationError,
            ];
        }

        // Tự động xóa khoa nếu chuyển thành vai trò quản trị viên hoặc kiểm duyệt viên
        if (in_array($role, [VaiTroNguoiDung::KIEM_DUYET_VIEN, VaiTroNguoiDung::QUAN_TRI_VIEN], true)) {
            $data['ma_khoa'] = null;
        }

        $adminLevel = $role === VaiTroNguoiDung::QUAN_TRI_VIEN
            ? CapQuanTri::from($data['cap_quan_tri'] ?? CapQuanTri::THUONG->value)
            : null;

        // Nếu quyền và khoa không thay đổi so với hiện tại
        if ($user->vai_tro?->value === $role->value && (int) $user->ma_khoa === (int) ($data['ma_khoa'] ?? 0)) {
            if ($user->cap_quan_tri === $adminLevel) {
                return [
                    'success' => true,
                    'type' => 'info',
                    'message' => 'Thông tin phân quyền không thay đổi.',
                ];
            }
        }

        // Thực thi cập nhật và lưu vết kiểm toán trong giao dịch CSDL nguyên tử
        DB::transaction(function () use ($user, $actor, $data, $role, $adminLevel): void {
            LichSuPhanQuyenNguoiDung::create([
                'ma_nguoi_dung' => $user->ma_nguoi_dung,
                'ma_nguoi_thuc_hien' => $actor->ma_nguoi_dung,
                'vai_tro_cu' => $user->vai_tro?->value,
                'vai_tro_moi' => $role->value,
                'cap_quan_tri_cu' => $user->cap_quan_tri?->value,
                'cap_quan_tri_moi' => $adminLevel?->value,
                'ma_khoa_cu' => $user->ma_khoa,
                'ma_khoa_moi' => $data['ma_khoa'] ?? null,
                'ly_do' => $data['ly_do'] ?? null,
            ]);

            app(AuditLogger::class)->log(
                actor: $actor,
                action: HanhDongKiemToan::THAY_DOI_VAI_TRO,
                targetType: LoaiDoiTuongKiemToan::NGUOI_DUNG,
                targetId: (string) $user->ma_nguoi_dung,
                description: "Thay đổi vai trò của {$user->ho_ten} thành {$role->value}" . ($adminLevel ? " ({$adminLevel->value})" : "") . ($data['ly_do'] ? ". Lý do: {$data['ly_do']}" : ""),
                oldData: [
                    'vai_tro' => $user->vai_tro?->value,
                    'cap_quan_tri' => $user->cap_quan_tri?->value,
                    'ma_khoa' => $user->ma_khoa,
                ],
                newData: [
                    'vai_tro' => $role->value,
                    'cap_quan_tri' => $adminLevel?->value,
                    'ma_khoa' => $data['ma_khoa'] ?? null,
                    'ly_do' => $data['ly_do'] ?? null,
                ]
            );

            $user->update([
                'vai_tro' => $role,
                'cap_quan_tri' => $adminLevel,
                'ma_khoa' => $data['ma_khoa'] ?? null,
            ]);
        });

        return [
            'success' => true,
            'type' => 'success',
            'message' => "Đã cập nhật vai trò và Khoa của {$user->ho_ten}.",
        ];
    }
}

