<?php

namespace App\Modules\Moderation\Application\Actions;

use App\Models\LichSuThungRac;
use App\Models\NguoiDung;
use App\Models\YeuCauGoBoTaiLieu;
use App\Notifications\DocumentRemovalRequestResolvedNotification;
use App\Modules\Moderation\Infrastructure\Persistence\RemovalRequestRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DecideRemovalRequestAction
{
    public function __construct(
        protected RemovalRequestRepository $repository
    ) {}

    // Quyết định duyệt hoặc từ chối yêu cầu xin gỡ tài liệu
    public function execute(int $requestId, string $action, ?string $reason, NguoiDung $actor): YeuCauGoBoTaiLieu
    {
        $removalRequest = DB::transaction(function () use ($requestId, $action, $reason, $actor): YeuCauGoBoTaiLieu {
            $removalRequest = $this->repository->findRemovalRequestForUpdate($requestId);

            // Kiểm tra trạng thái yêu cầu
            if ($removalRequest->trang_thai !== 'CHO_XU_LY') {
                abort(409, 'Yêu cầu gỡ này đã được xử lý trước đó.');
            }

            $now = now();
            $approved = ($action === 'APPROVE');

            $threshold = config('documents.community_ownership_download_threshold', 10);
            if ($approved && $removalRequest->taiLieu->luot_tai < $threshold) {
                throw ValidationException::withMessages([
                    'document' => 'Tài liệu không còn đáp ứng điều kiện sở hữu cộng đồng để được gỡ.',
                ]);
            }

            // Cập nhật trạng thái phán quyết
            $removalRequest->update([
                'trang_thai'     => $approved ? 'DA_DUYET' : 'TU_CHOI',
                'ma_nguoi_xu_ly' => $actor->getKey(),
                'xu_ly_luc'      => $now,
                'ly_do_tu_choi'  => $approved ? null : $reason,
            ]);

            // Nếu duyệt: Thực hiện xóa mềm tài liệu khỏi hệ thống tra cứu
            if ($approved) {
                $removalRequest->taiLieu->forceFill([
                    'ma_nguoi_xoa'      => $actor->getKey(),
                    'ly_do_xoa'         => $removalRequest->ly_do,
                    'nguon_xoa'         => 'TAC_GIA',
                    'han_luu_thung_rac' => now()->addDays(config('documents.trash_admin_delete_after_days', 60)),
                ])->save();

                $removalRequest->taiLieu->delete();

                LichSuThungRac::create([
                    'ma_tai_lieu'        => $removalRequest->taiLieu->getKey(),
                    'ma_nguoi_thuc_hien' => $actor->getKey(),
                    'hanh_dong'          => 'SOFT_DELETE',
                    'nguon'              => 'TAC_GIA',
                    'ly_do'              => $removalRequest->ly_do,
                ]);
            }

            return $removalRequest->fresh([
                'nguoiYeuCau',
                'taiLieu' => fn($query) => $query->withTrashed(),
            ]);
        });

        // Gửi thông báo kết quả cho tác giả
        $removalRequest->nguoiYeuCau?->notify(new DocumentRemovalRequestResolvedNotification(
            $removalRequest->load([
                'nguoiYeuCau',
                'taiLieu' => fn($query) => $query->withTrashed(),
            ])
        ));

        return $removalRequest;
    }
}

