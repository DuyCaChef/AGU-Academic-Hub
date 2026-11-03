<?php

namespace App\Modules\Administration\Application\Actions;

use App\Enums\TrangThaiNguoiDung;
use App\Models\LichSuTrangThaiNguoiDung;
use App\Models\NguoiDung;
use App\Modules\Administration\Domain\Enums\HanhDongKiemToan;
use App\Modules\Administration\Domain\Enums\LoaiDoiTuongKiemToan;
use App\Modules\Administration\Domain\Services\AdminPermissionRuleEvaluator;
use App\Modules\Administration\Domain\Services\AuditLogger;
use App\Modules\Administration\Infrastructure\Persistence\AdminUserRepository;
use Illuminate\Support\Facades\DB;

class ToggleUserStatusAction
{
    public function __construct(
        protected AdminUserRepository $userRepository,
        protected AdminPermissionRuleEvaluator $evaluator
    ) {}

    // Đảo chiều trạng thái hoạt động của tài khoản (Khóa hoặc Mở khóa)
    public function execute(NguoiDung $actor, int $maNguoiDung, ?string $inputReason): array
    {
        $user = $this->userRepository->findUserById($maNguoiDung);

        // Không được phép tự khóa tài khoản của chính mình
        if ($actor->is($user)) {
            return [
                'success' => false,
                'type' => 'error',
                'message' => 'Bạn không thể tự khóa hoặc mở khóa tài khoản của chính mình.',
            ];
        }

        // Kiểm tra quyền thao tác trên tài khoản quản trị
        if (!$this->evaluator->canManageTarget($actor, $user)) {
            return [
                'success' => false,
                'type' => 'error',
                'message' => 'Admin thường không được khóa hoặc mở khóa tài khoản quản trị.',
            ];
        }

        $newStatus = $user->trang_thai === TrangThaiNguoiDung::TAM_KHOA
            ? TrangThaiNguoiDung::DANG_HOAT_DONG
            : TrangThaiNguoiDung::TAM_KHOA;

        $action = $newStatus === TrangThaiNguoiDung::TAM_KHOA ? 'KHOA' : 'MO_KHOA';
        $reason = $inputReason ?? ($newStatus === TrangThaiNguoiDung::TAM_KHOA ? 'Quản trị viên khóa tài khoản.' : null);

        // Thực thi cập nhật trạng thái và ghi log audit trong giao dịch
        DB::transaction(function () use ($user, $actor, $newStatus, $action, $reason): void {
            LichSuTrangThaiNguoiDung::create([
                'ma_nguoi_dung' => $user->ma_nguoi_dung,
                'ma_nguoi_thuc_hien' => $actor->ma_nguoi_dung,
                'trang_thai_cu' => $user->trang_thai?->value,
                'trang_thai_moi' => $newStatus->value,
                'hanh_dong' => $action,
                'ly_do' => $reason,
            ]);

            $auditAction = $action === 'KHOA' ? HanhDongKiemToan::KHOA_TAI_KHOAN : HanhDongKiemToan::MO_KHOA_TAI_KHOAN;
            $auditDesc = ($action === 'KHOA' ? 'Khóa' : 'Mở khóa') . " tài khoản của {$user->ho_ten} ({$user->email})." . ($reason ? " Lý do: {$reason}" : '');

            app(AuditLogger::class)->log(
                actor: $actor,
                action: $auditAction,
                targetType: LoaiDoiTuongKiemToan::NGUOI_DUNG,
                targetId: (string) $user->ma_nguoi_dung,
                description: $auditDesc,
                oldData: ['trang_thai' => $user->trang_thai?->value],
                newData: ['trang_thai' => $newStatus->value, 'ly_do' => $reason]
            );

            $user->update([
                'trang_thai' => $newStatus,
                'ly_do_khoa' => $newStatus === TrangThaiNguoiDung::TAM_KHOA ? $reason : null,
            ]);
        });

        $flashType = $newStatus === TrangThaiNguoiDung::TAM_KHOA ? 'warning' : 'success';
        $flashMessage = $newStatus === TrangThaiNguoiDung::TAM_KHOA
            ? "Đã tạm khóa tài khoản của {$user->ho_ten}."
            : "Đã mở khóa và kích hoạt lại tài khoản của {$user->ho_ten}.";

        return [
            'success' => true,
            'type' => $flashType,
            'message' => $flashMessage,
        ];
    }
}

