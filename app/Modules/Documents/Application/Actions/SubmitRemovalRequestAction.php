<?php

namespace App\Modules\Documents\Application\Actions;

use App\Models\NguoiDung;
use App\Models\TaiLieu;
use App\Models\YeuCauGoBoTaiLieu;
use App\Notifications\DocumentRemovalRequestSubmittedNotification;
use App\Modules\Documents\Domain\Services\DocumentAuthorRemovalEvaluator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubmitRemovalRequestAction
{
    public function __construct(
        protected DocumentAuthorRemovalEvaluator $evaluator
    ) {}

    // Gửi yêu cầu xin gỡ tài liệu đã thuộc quyền sở hữu cộng đồng
    public function execute(int $documentId, string $reason, NguoiDung $user): YeuCauGoBoTaiLieu
    {
        $document = TaiLieu::query()->findOrFail($documentId);

        // Đối soát điều kiện sở hữu cộng đồng
        $check = $this->evaluator->canRequestRemoval($document, $user);
        if (!$check['allowed']) {
            if ($check['status'] === 403) {
                abort(403, $check['message']);
            }
            throw ValidationException::withMessages([
                'document' => $check['message'],
            ]);
        }

        // Tạo yêu cầu gỡ có khóa bi quan (Pessimistic Lock) chống gửi đơn trùng lặp
        $removalRequest = DB::transaction(function () use ($document, $reason, $user): YeuCauGoBoTaiLieu {
            $hasPendingRequest = YeuCauGoBoTaiLieu::query()
                ->where('ma_tai_lieu', $document->getKey())
                ->where('ma_nguoi_yeu_cau', $user->getKey())
                ->where('trang_thai', 'CHO_XU_LY')
                ->lockForUpdate()
                ->exists();

            if ($hasPendingRequest) {
                throw ValidationException::withMessages([
                    'document' => 'Tài liệu đã có một yêu cầu gỡ đang chờ xử lý.',
                ]);
            }

            return YeuCauGoBoTaiLieu::create([
                'ma_tai_lieu'      => $document->getKey(),
                'ma_nguoi_yeu_cau' => $user->getKey(),
                'ly_do'            => $reason,
            ]);
        });

        // Gửi thông báo đến ban quản trị / kiểm duyệt viên
        $removalRequest->load('taiLieu');
        NguoiDung::query()
            ->whereIn('vai_tro', ['KIEM_DUYET_VIEN', 'QUAN_TRI_VIEN'])
            ->get()
            ->each(fn(NguoiDung $moderator) => $moderator->notify(
                new DocumentRemovalRequestSubmittedNotification($removalRequest)
            ));

        return $removalRequest;
    }
}
