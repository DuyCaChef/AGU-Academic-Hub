<?php

namespace App\Modules\Identity\Application\Actions;

use App\Enums\TrangThaiXacThuc;
use App\Models\NguoiDung;
use App\Models\YeuCauXacThuc;
use App\Modules\Administration\Domain\Enums\HanhDongKiemToan;
use App\Modules\Administration\Domain\Enums\LoaiDoiTuongKiemToan;
use App\Modules\Administration\Domain\Services\AuditLogger;
use App\Modules\Identity\Domain\Services\AlumniKycRuleEvaluator;
use App\Modules\Identity\Infrastructure\Notifications\AlumniKycDecisionNotification;
use App\Modules\Identity\Infrastructure\Persistence\KycRepository;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RejectKycAction
{
    public function __construct(
        protected KycRepository $repository,
        protected AlumniKycRuleEvaluator $ruleEvaluator
    ) {}

    // Từ chối hồ sơ KYC cựu sinh viên kèm lý do cụ thể
    public function execute(int $maXacThuc, string $reason, NguoiDung $reviewer): YeuCauXacThuc
    {
        return DB::transaction(function () use ($maXacThuc, $reason, $reviewer) {
            $kyc = $this->repository->findAlumniKycForUpdate($maXacThuc);

            if (!$this->ruleEvaluator->canReject($kyc)) {
                throw new RuntimeException('Hồ sơ KYC cựu sinh viên không còn hợp lệ để từ chối.');
            }

            // Cập nhật trạng thái từ chối hồ sơ
            $kyc->update([
                'trang_thai'      => TrangThaiXacThuc::TU_CHOI,
                'nguoi_duyet'     => $reviewer->ma_nguoi_dung,
                'thoi_gian_duyet' => now(),
                'ly_do_tu_choi'   => $reason,
            ]);

            // Gửi thông báo từ chối kèm lý do tới người dùng
            $kyc->nguoiDung->notify(new AlumniKycDecisionNotification(
                'TU_CHOI',
                'Hồ sơ cựu sinh viên bị từ chối: ' . $reason,
                $reason
            ));

            // Ghi nhận nhật ký kiểm toán
            app(AuditLogger::class)->log(
                actor: $reviewer,
                action: HanhDongKiemToan::TU_CHOI_KYC,
                targetType: LoaiDoiTuongKiemToan::YEU_CAU_XAC_THUC,
                targetId: (string) $kyc->ma_yeu_cau_xac_thuc,
                description: "Từ chối hồ sơ KYC cựu sinh viên của {$kyc->nguoiDung->ho_ten}. Lý do: {$reason}",
                oldData: ['trang_thai' => TrangThaiXacThuc::CHO_DUYET->value],
                newData: ['trang_thai' => TrangThaiXacThuc::TU_CHOI->value, 'nguoi_duyet' => $reviewer->ma_nguoi_dung, 'ly_do_tu_choi' => $reason]
            );

            return $kyc;
        });
    }
}

