<?php

namespace App\Modules\Identity\Application\Actions;

use App\Enums\TrangThaiNguoiDung;
use App\Enums\TrangThaiXacThuc;
use App\Enums\VaiTroNguoiDung;
use App\Models\NguoiDung;
use App\Models\YeuCauXacThuc;
use App\Notifications\KycDecisionNotification;
use App\Modules\Identity\Domain\Services\AlumniKycRuleEvaluator;
use App\Modules\Identity\Infrastructure\Persistence\KycRepository;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ApproveKycAction
{
    public function __construct(
        protected KycRepository $repository,
        protected AlumniKycRuleEvaluator $ruleEvaluator
    ) {}

    // Phê duyệt hồ sơ KYC cựu sinh viên, kích hoạt tài khoản và cấp ví điểm thưởng ban đầu
    public function execute(int $maXacThuc, NguoiDung $reviewer): YeuCauXacThuc
    {
        return DB::transaction(function () use ($maXacThuc, $reviewer) {
            $kyc = $this->repository->findAlumniKycForUpdate($maXacThuc);

            if (!$this->ruleEvaluator->canApprove($kyc)) {
                throw new RuntimeException('Hồ sơ KYC cựu sinh viên không còn hợp lệ để phê duyệt.');
            }

            // Cập nhật trạng thái duyệt hồ sơ
            $kyc->update([
                'trang_thai'      => TrangThaiXacThuc::DA_DUYET,
                'nguoi_duyet'     => $reviewer->ma_nguoi_dung,
                'thoi_gian_duyet' => now(),
                'ly_do_tu_choi'   => null,
            ]);

            // Kích hoạt tài khoản người dùng
            $kyc->nguoiDung()->update([
                'trang_thai' => TrangThaiNguoiDung::DANG_HOAT_DONG,
                'vai_tro'    => VaiTroNguoiDung::CUU_SINH_VIEN,
            ]);

            // Khởi tạo ví điểm ban đầu (100 điểm khả dụng)
            $kyc->nguoiDung->viDiem()->firstOrCreate([], [
                'diem_kha_dung' => 100,
                'diem_tam_giu'  => 0,
            ]);

            // Gửi thông báo phê duyệt tới tài khoản người dùng
            $kyc->nguoiDung->notify(new KycDecisionNotification(
                'DA_DUYET',
                'Hồ sơ cựu sinh viên đã được duyệt. Tài khoản của bạn đã được kích hoạt.'
            ));

            return $kyc;
        });
    }
}
