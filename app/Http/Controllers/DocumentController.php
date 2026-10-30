<?php

namespace App\Http\Controllers;

use App\Models\NguoiDung;
use App\Models\LichSuThungRac;
use App\Models\TaiLieu;
use App\Models\YeuCauGoBoTaiLieu;
use App\Notifications\DocumentRemovalRequestSubmittedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DocumentController extends Controller
{

    // Tác giả tự xóa mềm tài liệu của mình (chỉ trong 24 giờ đầu và chưa có lượt tải).
    public function destroy(Request $request, int $id): RedirectResponse
    {

        // 1. Kiểm thực lý do xóa bắt buộc để lưu vết kiểm toán
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);
        $document = TaiLieu::query()->findOrFail($id);

        // 2. Chốt chặn 3 lớp theo quy định bảo vệ tài liệu
        abort_unless($document->ma_nguoi_dang === $request->user()->getKey(), 403, 'Chỉ tác giả mới được xóa tài liệu.');
        abort_if($document->luot_tai > 0, 422, 'Tài liệu đã có lượt tải và không thể tự xóa.');
        abort_if($document->created_at?->lt(now()->subDay()), 422, 'Chỉ có thể tự xóa tài liệu trong 24 giờ đầu.');

        // 3. Thực thi xóa mềm và ghi vết lịch sử thùng rác trong giao dịch an toàn (ACID)
        DB::transaction(function () use ($data, $document, $request): void {
            // Cập nhật thông tin kiểm toán trước khi xóa mềm
            $document->forceFill([
                'ma_nguoi_xoa' => $request->user()->getKey(),
                'ly_do_xoa' => $data['reason'],
                'nguon_xoa' => 'TAC_GIA',
                'han_luu_thung_rac' => now()->addDays(config('documents.trash_admin_delete_after_days')),
            ])->save();
            $document->delete();

            // Ghi nhật ký vào bảng kiểm toán thùng rác
            LichSuThungRac::create([
                'ma_tai_lieu' => $document->getKey(),
                'ma_nguoi_thuc_hien' => $request->user()->getKey(),
                'hanh_dong' => 'SOFT_DELETE',
                'nguon' => 'TAC_GIA',
                'ly_do' => $data['reason'],
            ]);
        });

        return back()->with('success', 'Đã chuyển tài liệu vào thùng rác.');
    }

    // Gửi yêu cầu xin gỡ tài liệu đã thuộc quyền sở hữu cộng đồng.
    public function requestRemoval(Request $request, int $id): RedirectResponse
    {
        // 1. Kiểm thực lý do xin gỡ của tác giả
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $document = TaiLieu::query()->findOrFail($id);
        $user = $request->user();
        $threshold = config('documents.community_ownership_download_threshold');

        // 2. Kiểm tra quyền tác giả
        if ($document->ma_nguoi_dang !== $user->getKey()) {
            abort(403, 'Chỉ tác giả của tài liệu mới được gửi yêu cầu gỡ.');
        }

        // 3. Đối soát điều kiện sở hữu cộng đồng (Community Ownership)
        if ($document->luot_tai < $threshold) {
            throw ValidationException::withMessages([
                'document' => "Tài liệu chỉ thuộc quyền sở hữu cộng đồng từ {$threshold} lượt tải.",
            ]);
        }

        // 4. Tạo yêu cầu gỡ có khóa bi quan (Pessimistic Lock) chống gửi đơn trùng lặp
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

        // 5. Nạp trước quan hệ tài liệu và gửi thông báo chuông đến ban quản trị
        $removalRequest->load('taiLieu');
        NguoiDung::query()
            ->whereIn('vai_tro', ['KIEM_DUYET_VIEN', 'QUAN_TRI_VIEN'])
            ->get()
            ->each(fn(NguoiDung $moderator) => $moderator->notify(
                new DocumentRemovalRequestSubmittedNotification($removalRequest)
            ));

        return back()->with('success', 'Đã gửi yêu cầu gỡ tài liệu đến hàng đợi xét duyệt.');
    }
}
