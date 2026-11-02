<?php

namespace App\Modules\Administration\Domain\Services;

use App\Enums\CapQuanTri;
use App\Enums\VaiTroNguoiDung;
use App\Models\NguoiDung;

class AdminPermissionRuleEvaluator
{
    // Kiểm tra quyền của tài khoản thực hiện (actor) đối với tài khoản mục tiêu (target)
    public function canManageTarget(NguoiDung $actor, NguoiDung $target): bool
    {
        // Không được phép tự thao tác trên chính tài khoản của mình
        if ($actor->is($target)) {
            return false;
        }

        // Quản trị viên và Kiểm duyệt viên chỉ có thể bị quản lý bởi Super Admin
        if ($target->laQuanTriVien() || $target->laKiemDuyetVien()) {
            return $actor->laSuperAdmin();
        }

        return true;
    }

    // Đánh giá tính hợp lệ của việc cập nhật phân quyền người dùng
    public function validatePermissionUpdate(
        NguoiDung $actor,
        NguoiDung $target,
        VaiTroNguoiDung $role,
        ?string $capQuanTriInput,
        ?int $maKhoa,
        int $currentSuperAdminCount
    ): ?string {
        // Kiểm tra không được tự thay đổi quyền của chính mình
        if ($actor->is($target)) {
            return 'Bạn không thể tự thay đổi vai trò hoặc Khoa của tài khoản mình.';
        }

        // Kiểm tra thẩm quyền quản trị của actor đối với target
        $isTargetAdminOrMod = in_array($role, [VaiTroNguoiDung::KIEM_DUYET_VIEN, VaiTroNguoiDung::QUAN_TRI_VIEN], true);
        if (!$this->canManageTarget($actor, $target) || (!$actor->laSuperAdmin() && $isTargetAdminOrMod)) {
            return 'Admin thường không được thay đổi quyền quản trị của tài khoản này.';
        }

        // Chỉ Super Admin mới có quyền cấp vai trò Quản trị viên
        if ($role === VaiTroNguoiDung::QUAN_TRI_VIEN && !$actor->laSuperAdmin()) {
            return 'Chỉ Super Admin mới được cấp quyền Admin.';
        }

        // Không được hạ cấp nếu đây là Super Admin cuối cùng của hệ thống
        $isDemotingSuperAdmin = $target->laSuperAdmin()
            && ($role !== VaiTroNguoiDung::QUAN_TRI_VIEN || $capQuanTriInput !== CapQuanTri::TOI_CAO->value);

        if ($isDemotingSuperAdmin && $currentSuperAdminCount <= 1) {
            return 'Không thể hạ cấp Super Admin cuối cùng.';
        }

        return null;
    }

    // Đánh giá mức độ vi phạm tiếp theo khi ghi nhận vi phạm
    public function evaluateViolationProgression(int $currentCount): array
    {
        $newCount = $currentCount + 1;
        $level = $newCount === 1 ? 'CANH_BAO' : 'TAM_KHOA';

        return [
            'new_count' => $newCount,
            'level' => $level,
            'should_lock' => $level === 'TAM_KHOA',
        ];
    }
}

