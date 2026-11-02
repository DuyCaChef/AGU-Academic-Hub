<?php

namespace App\Modules\Bounties\Application\Actions;

use App\Enums\TrangThaiYeuCauTaiLieu;
use App\Models\NguoiDung;
use App\Models\YeuCauTaiLieu;
use App\Modules\Bounties\Domain\Services\ReputationEvaluator;
use App\Modules\Bounties\Infrastructure\Persistence\WishlistBountyRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

// Action tạo yêu cầu tìm tài liệu mới kèm đóng băng điểm thưởng
class CreateBountyRequestAction
{
    public function __construct(
        protected WishlistBountyRepository $bountyRepository,
        protected ReputationEvaluator $reputationEvaluator
    ) {}

    public function execute(NguoiDung $user, array $validated): YeuCauTaiLieu
    {
        // Không cho tạo yêu cầu mới cho Môn học thuộc Khoa đã vô hiệu hóa
        if (!$this->bountyRepository->isCourseActive((int) $validated['ma_mon_hoc'])) {
            throw ValidationException::withMessages([
                'ma_mon_hoc' => 'Môn học hiện không khả dụng vì Khoa đang bị vô hiệu hóa.',
            ]);
        }

        $bounty = (int) $validated['diem_thuong'];

        return DB::transaction(function () use ($validated, $user, $bounty) {
            // 1. Đóng băng điểm thưởng từ ví người tạo
            $tx = $this->reputationEvaluator->dongBangDiemBounty($user, $bounty);

            // 2. Tạo bản ghi YeuCauTaiLieu
            $docRequest = $this->bountyRepository->createRequest([
                'ma_mon_hoc'     => $validated['ma_mon_hoc'],
                'ma_nguoi_tao'   => $user->ma_nguoi_dung,
                'tieu_de'        => trim($validated['tieu_de']),
                'mo_ta'          => $validated['mo_ta'] ?? null,
                'ten_giang_vien' => $validated['ten_giang_vien'] ?? null,
                'phan_loai'      => $validated['phan_loai'],
                'diem_thuong'    => $bounty,
                'trang_thai'     => TrangThaiYeuCauTaiLieu::MO,
            ]);

            // Cập nhật lại ma_tham_chieu vào bản ghi GiaoDichDiem
            $tx->update(['ma_tham_chieu' => $docRequest->ma_yeu_cau]);

            return $docRequest;
        });
    }
}

