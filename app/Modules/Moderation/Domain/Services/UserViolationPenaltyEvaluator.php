<?php

namespace App\Modules\Moderation\Domain\Services;

use App\Enums\TrangThaiNguoiDung;
use App\Models\LichSuTrangThaiNguoiDung;
use App\Models\NguoiDung;
use App\Modules\Administration\Domain\Enums\HanhDongKiemToan;
use App\Modules\Administration\Domain\Enums\LoaiDoiTuongKiemToan;
use App\Modules\Administration\Domain\Services\AuditLogger;
use App\Modules\Moderation\Infrastructure\Notifications\UserViolationSanctionedNotification;

class UserViolationPenaltyEvaluator
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    // Áp dụng chế tài trừ điểm uy tín và lũy tiến vi phạm cho tác giả khi nội dung bị xử lý xóa do vi phạm
    public function applyPenalty(
        ?NguoiDung $author,
        NguoiDung $actor,
        string $reason,
        string $targetTitle,
        int $penaltyPoints = 20
    ): array {
        if (!$author) {
            return [
                'applied' => false,
                'message' => 'Không tìm thấy tác giả nội dung.',
            ];
        }

        // Không xử lý tự phạt chính mình
        if ($author->is($actor)) {
            return [
                'applied' => false,
                'message' => 'Người thực hiện không thể tự phạt tài khoản của chính mình.',
            ];
        }

        // Không áp dụng chế tài tự động đối với tài khoản quản trị viên
        if ($author->laQuanTriVien()) {
            return [
                'applied' => false,
                'message' => 'Không áp dụng chế tài tự động đối với tài khoản quản trị viên.',
            ];
        }

        // 1. Tính toán điểm uy tín mới (sàn tối thiểu là 0)
        $oldReputation = (int) ($author->diem_uy_tin ?? 100);
        $newReputation = max(0, $oldReputation - $penaltyPoints);

        // 2. Tính toán lũy tiến số lần vi phạm và mức cảnh báo theo quy chế
        $currentCount = (int) ($author->so_lan_vi_pham ?? 0);
        $newCount = $currentCount + 1;
        $level = $newCount === 1 ? 'CANH_BAO' : 'TAM_KHOA';
        $shouldLock = ($level === 'TAM_KHOA');

        $oldWarningLevel = $author->muc_canh_bao;
        $oldStatus = $author->trang_thai?->value ?? TrangThaiNguoiDung::DANG_HOAT_DONG->value;
        $newStatus = $shouldLock ? TrangThaiNguoiDung::TAM_KHOA : $author->trang_thai;

        // 3. Cập nhật hồ sơ người dùng
        $author->diem_uy_tin = $newReputation;
        $author->so_lan_vi_pham = $newCount;
        $author->muc_canh_bao = $level;

        if ($shouldLock) {
            $author->trang_thai = TrangThaiNguoiDung::TAM_KHOA;
            $author->ly_do_khoa = $reason;
        }

        $author->save();

        // 4. Ghi lịch sử trạng thái người dùng nếu bị chuyển sang tạm khóa
        if ($shouldLock) {
            LichSuTrangThaiNguoiDung::create([
                'ma_nguoi_dung'      => $author->ma_nguoi_dung,
                'ma_nguoi_thuc_hien' => $actor->ma_nguoi_dung,
                'trang_thai_cu'      => $oldStatus,
                'trang_thai_moi'     => TrangThaiNguoiDung::TAM_KHOA->value,
                'hanh_dong'          => 'TAM_KHOA',
                'ly_do'              => "Tự động tạm khóa do tái phạm vi phạm nội dung (lần {$newCount}). Căn cứ: {$reason}",
            ]);
        }

        // 5. Ghi nhật ký kiểm toán (Audit Trail)
        $this->auditLogger->log(
            actor: $actor,
            action: HanhDongKiemToan::GHI_NHAN_VI_PHAM,
            targetType: LoaiDoiTuongKiemToan::NGUOI_DUNG,
            targetId: (string) $author->ma_nguoi_dung,
            description: "Chế tài vi phạm nội dung cho {$author->ho_ten} ({$author->email}): -{$penaltyPoints} điểm uy tín (còn {$newReputation}), mức cảnh báo: {$level}, vi phạm lần thứ {$newCount}. Đối tượng vi phạm: \"{$targetTitle}\". Lý do: {$reason}",
            oldData: [
                'diem_uy_tin'   => $oldReputation,
                'muc_canh_bao'  => $oldWarningLevel,
                'so_lan_vi_pham' => $currentCount,
                'trang_thai'    => $oldStatus,
            ],
            newData: [
                'diem_uy_tin'   => $newReputation,
                'muc_canh_bao'  => $level,
                'so_lan_vi_pham' => $newCount,
                'trang_thai'    => $newStatus?->value ?? $oldStatus,
                'ly_do'         => $reason,
            ]
        );

        // 6. Gửi thông báo đến tác giả vi phạm
        $author->notify(new UserViolationSanctionedNotification(
            targetTitle: $targetTitle,
            penaltyPoints: $penaltyPoints,
            newReputation: $newReputation,
            violationCount: $newCount,
            warningLevel: $level,
            isLocked: $shouldLock,
            reason: $reason
        ));

        return [
            'applied'        => true,
            'penalty_points' => $penaltyPoints,
            'new_reputation' => $newReputation,
            'so_lan_vi_pham' => $newCount,
            'muc_canh_bao'   => $level,
            'is_locked'      => $shouldLock,
        ];
    }
}

