<?php

namespace App\Modules\Bounties\Application\Actions;

use App\Enums\TrangThaiYeuCauTaiLieu;
use App\Models\NguoiDung;
use App\Models\VaiTroNguoiDung;
use App\Modules\Bounties\Domain\Services\BountyRuleEvaluator;
use App\Modules\Bounties\Infrastructure\Notifications\BountyPayoutApprovalRequiredNotification;
use App\Modules\Bounties\Infrastructure\Persistence\WishlistBountyRepository;
use App\Modules\Notifications\Domain\Events\BountyFulfillmentConfirmedEvent;
use Illuminate\Support\Facades\DB;

// Action xử lý xác nhận của người yêu cầu - chuyển trạng thái sang CHO_GIAI_NGAN và thông báo Admin/Mod
class ConfirmBountyFulfillmentAction
{
    public function __construct(
        protected WishlistBountyRepository $bountyRepository,
        protected BountyRuleEvaluator $ruleEvaluator
    ) {
    }

    public function execute(NguoiDung $user, int $maYeuCau, int $maDapUng): array
    {
        $yeuCau = $this->bountyRepository->findRequestById($maYeuCau);

        // Quyền xác nhận: Chỉ người tạo yêu cầu mới được xác nhận
        if (!$this->ruleEvaluator->canConfirm($user, $yeuCau)) {
            abort(403, 'Bạn không có quyền xác nhận bài đóng góp cho yêu cầu này.');
        }

        // Yêu cầu phải đang ở trạng thái MO
        if ($yeuCau->trang_thai !== TrangThaiYeuCauTaiLieu::MO) {
            return [
                'success' => false,
                'message' => 'Yêu cầu này đã được xử lý hoặc đã bị hủy, không thể xác nhận.',
            ];
        }

        $dapUng = $this->bountyRepository->findSubmission($yeuCau->ma_yeu_cau, $maDapUng);

        // Bài nộp phải đang ở trạng thái CHO_DUYET
        if ($dapUng->trang_thai !== 'CHO_DUYET') {
            return [
                'success' => false,
                'message' => 'Bài đóng góp này không ở trạng thái hợp lệ để xác nhận.',
            ];
        }

        // Chặn nếu tài liệu đóng góp đã bị Ban kiểm duyệt từ chối
        if ($dapUng->taiLieu && $dapUng->taiLieu->trang_thai === 'TU_CHOI') {
            return [
                'success' => false,
                'message' => 'Tài liệu đóng góp này đã bị Ban kiểm duyệt từ chối, không thể xác nhận.',
            ];
        }

        DB::transaction(function () use ($yeuCau, $dapUng) {
            // Đánh dấu bài nộp được người yêu cầu chọn
            $dapUng->update(['trang_thai' => 'NGUOI_YEU_CAU_XAC_NHAN']);

            // Chuyển yêu cầu sang trạng thái CHO_GIAI_NGAN - chờ Ban kiểm duyệt xác nhận tính hợp lệ
            $yeuCau->update([
                'trang_thai' => TrangThaiYeuCauTaiLieu::CHO_GIAI_NGAN,
                'ma_dap_ung_duoc_chon' => $dapUng->ma_dap_ung,
                'thoi_gian_nguoi_yeu_cau_xac_nhan' => now(),
            ]);
        });

        // Thông báo đến tất cả Admin và Kiểm duyệt viên về yêu cầu duyệt tài liệu Bounty
        $tenNguoiYeuCau = $yeuCau->nguoiTao->ho_ten ?? 'Sinh viên';
        $tenNguoiDongGop = $dapUng->nguoiDongGop->ho_ten ?? 'Người đóng góp';

        $adminsAndMods = NguoiDung::whereIn('vai_tro', ['QUAN_TRI_VIEN', 'KIEM_DUYET_VIEN'])->get();
        $adminsAndMods->each(function (NguoiDung $recipient) use ($yeuCau, $dapUng, $tenNguoiYeuCau, $tenNguoiDongGop) {
            $recipient->notify(new BountyPayoutApprovalRequiredNotification(
                maYeuCau: $yeuCau->ma_yeu_cau,
                maDapUng: $dapUng->ma_dap_ung,
                diemThuong: $yeuCau->diem_thuong,
                tenNguoiYeuCau: $tenNguoiYeuCau,
                tenNguoiDongGop: $tenNguoiDongGop
            ));
        });

        return [
            'success' => true,
            'message' => "Đã xác nhận bài đóng góp! Yêu cầu đang được gửi tới Ban kiểm duyệt để kiểm tra tài liệu. Khi tài liệu được xác nhận hợp lệ, điểm thưởng sẽ tự động cộng ngay cho {$tenNguoiDongGop}.",
        ];
    }
}
