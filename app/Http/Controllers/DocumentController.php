<?php

namespace App\Http\Controllers;

use App\Models\NguoiDung;
use App\Models\TaiLieu;
use App\Models\YeuCauGoBoTaiLieu;
use App\Notifications\DocumentRemovalRequestSubmittedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DocumentController extends Controller
{
    public function requestRemoval(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $document = TaiLieu::query()->findOrFail($id);
        $user = $request->user();
        $threshold = config('documents.community_ownership_download_threshold');

        if ($document->ma_nguoi_dang !== $user->getKey()) {
            abort(403, 'Chỉ tác giả của tài liệu mới được gửi yêu cầu gỡ.');
        }

        if ($document->luot_tai < $threshold) {
            throw ValidationException::withMessages([
                'document' => "Tài liệu chỉ thuộc quyền sở hữu cộng đồng từ {$threshold} lượt tải.",
            ]);
        }

        $removalRequest = DB::transaction(function () use ($data, $document, $user): YeuCauGoBoTaiLieu {
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
                'ma_tai_lieu' => $document->getKey(),
                'ma_nguoi_yeu_cau' => $user->getKey(),
                'ly_do' => $data['reason'],
            ]);
        });

        $removalRequest->load('taiLieu');
        NguoiDung::query()
            ->whereIn('vai_tro', ['KIEM_DUYET_VIEN', 'QUAN_TRI_VIEN'])
            ->get()
            ->each(fn (NguoiDung $moderator) => $moderator->notify(
                new DocumentRemovalRequestSubmittedNotification($removalRequest)
            ));

        return back()->with('success', 'Đã gửi yêu cầu gỡ tài liệu đến hàng đợi xét duyệt.');
    }
}
