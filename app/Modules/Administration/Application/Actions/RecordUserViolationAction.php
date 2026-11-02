<?php

namespace App\Modules\Administration\Application\Actions;

use App\Enums\TrangThaiNguoiDung;
use App\Models\LichSuTrangThaiNguoiDung;
use App\Models\NguoiDung;
use App\Modules\Administration\Domain\Services\AdminPermissionRuleEvaluator;
use App\Modules\Administration\Infrastructure\Persistence\AdminUserRepository;
use Illuminate\Support\Facades\DB;

class RecordUserViolationAction
{
    public function __construct(
        protected AdminUserRepository $userRepository,
        protected AdminPermissionRuleEvaluator $evaluator
    ) {}

    // Ghi nhận vi phạm, tự động nâng cấp mức cảnh báo hoặc tạm khóa tài khoản nếu vi phạm lặp lại
    public function execute(NguoiDung $actor, int $maNguoiDung, string $reason): array
    {
        $user = $this->userRepository->findUserById($maNguoiDung);

        // Không được phép tự ghi nhận vi phạm cho tài khoản của mình
        if ($actor->is($user)) {
            return [
                'success' => false,
                'type' => 'error',
                'message' => 'Bạn không thể tự ghi nhận vi phạm cho tài khoản của mình.',
            ];
        }

        // Kiểm tra thẩm quyền quản trị của actor đối với target
        if (!$this->evaluator->canManageTarget($actor, $user)) {
            return [
                'success' => false,
                'type' => 'error',
                'message' => 'Admin thường không được xử lý vi phạm của tài khoản quản trị.',
            ];
        }

        $progression = $this->evaluator->evaluateViolationProgression((int) $user->so_lan_vi_pham);
        $count = $progression['new_count'];
        $level = $progression['level'];

        // Thực thi cập nhật số lần vi phạm và ghi nhật ký trạng thái
        DB::transaction(function () use ($user, $actor, $reason, $count, $level): void {
            $oldStatus = $user->trang_thai?->value;
            $newStatus = $level === 'TAM_KHOA' ? TrangThaiNguoiDung::TAM_KHOA : $user->trang_thai;

            $user->update([
                'so_lan_vi_pham' => $count,
                'muc_canh_bao' => $level,
                'trang_thai' => $newStatus,
                'ly_do_khoa' => $newStatus === TrangThaiNguoiDung::TAM_KHOA ? $reason : $user->ly_do_khoa,
            ]);

            if ($newStatus !== $user->trang_thai || $newStatus === TrangThaiNguoiDung::TAM_KHOA) {
                LichSuTrangThaiNguoiDung::create([
                    'ma_nguoi_dung' => $user->ma_nguoi_dung,
                    'ma_nguoi_thuc_hien' => $actor->ma_nguoi_dung,
                    'trang_thai_cu' => $oldStatus,
                    'trang_thai_moi' => $newStatus->value,
                    'hanh_dong' => $level,
                    'ly_do' => $reason,
                ]);
            }
        });

        $flashType = $level === 'TAM_KHOA' ? 'warning' : 'success';
        $flashMessage = $level === 'TAM_KHOA'
            ? 'Tài khoản đã bị tạm khóa do vi phạm lặp lại.'
            : 'Đã ghi nhận cảnh báo vi phạm.';

        return [
            'success' => true,
            'type' => $flashType,
            'message' => $flashMessage,
        ];
    }
}

