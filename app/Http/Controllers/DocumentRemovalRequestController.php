<?php

namespace App\Http\Controllers;

use App\Models\YeuCauGoBoTaiLieu;
use App\Models\LichSuThungRac;
use App\Notifications\DocumentRemovalRequestResolvedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class DocumentRemovalRequestController extends Controller
{

    // Hiển thị danh sách hàng đợi yêu cầu gỡ bỏ tài liệu
    public function index(Request $request): Response
    {
        $requests = YeuCauGoBoTaiLieu::with([
            'taiLieu' => fn ($query) => $query->withTrashed()->with(['monHoc', 'nguoiDang']),
            'nguoiYeuCau',
            'nguoiXuLy',
        ])
            ->when($request->filled('status'), fn($query) => $query->where('trang_thai', $request->string('status')))
            ->latest('ma_yeu_cau_go_bo')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Documents/RemovalRequests/Index', [
            'requests' => $requests,
            'filters' => $request->only('status'),
            'communityOwnershipThreshold' => config('documents.community_ownership_download_threshold'),
        ]);
    }

    // Xem hồ sơ chi tiết một yêu cầu xin gỡ tài liệu.
    public function show(int $id): Response
    {
        $removalRequest = YeuCauGoBoTaiLieu::with([
            'taiLieu.monHoc',
            'taiLieu.nguoiDang',
            'nguoiYeuCau',
            'nguoiXuLy',
        ])->findOrFail($id);

        return Inertia::render('Admin/Documents/RemovalRequests/Show', [
            'removalRequest' => $removalRequest,
            'communityOwnershipThreshold' => config('documents.community_ownership_download_threshold'),
        ]);
    }

    // Quyết định duyệt hay từ chối một yêu cầu gỡ bỏ tài liệu.
    public function decide(Request $request, int $id): RedirectResponse
    {
        // 1. Kiểm thực dữ liệu đầu vào: Bắt buộc nhập lý do nếu chọn Từ chối (REJECT)
        $data = $request->validate([
            'action' => ['required', Rule::in(['APPROVE', 'REJECT'])],
            'decision_reason' => ['nullable', 'required_if:action,REJECT', 'string', 'max:2000'],
        ]);

        // 2. Thực thi nghiệp vụ bên trong Transaction an toàn
        $removalRequest = DB::transaction(function () use ($data, $id, $request): YeuCauGoBoTaiLieu {
            $removalRequest = YeuCauGoBoTaiLieu::query()
                ->with(['taiLieu', 'nguoiYeuCau'])
                ->lockForUpdate()
                ->findOrFail($id);

            // Chốt chặn trạng thái: Nếu đơn đã được xử lý bởi người khác trước đó, ngắt tiến trình
            if ($removalRequest->trang_thai !== 'CHO_XU_LY') {
                abort(409, 'Yêu cầu gỡ này đã được xử lý trước đó.');
            }

            $now = now();
            $approved = $data['action'] === 'APPROVE';
            if ($approved && $removalRequest->taiLieu->luot_tai < config('documents.community_ownership_download_threshold')) {
                throw ValidationException::withMessages([
                    'document' => 'Tài liệu không còn đáp ứng điều kiện sở hữu cộng đồng để được gỡ.',
                ]);
            }

            // Cập nhật trạng thái phán quyết và lưu vết kiểm toán (Audit Trail)
            $removalRequest->update([
                'trang_thai' => $approved ? 'DA_DUYET' : 'TU_CHOI',
                'ma_nguoi_xu_ly' => $request->user()->getKey(),
                'xu_ly_luc' => $now,
                'ly_do_tu_choi' => $approved ? null : $data['decision_reason'],
            ]);

            // Nếu duyệt: Thực hiện Xóa Mềm (Soft Delete) tài liệu khỏi cổng tra cứu cộng đồng
            if ($approved) {
                $removalRequest->taiLieu->forceFill([
                    'ma_nguoi_xoa' => $request->user()->getKey(),
                    'ly_do_xoa' => $removalRequest->ly_do,
                    'nguon_xoa' => 'TAC_GIA',
                    'han_luu_thung_rac' => now()->addDays(config('documents.trash_admin_delete_after_days')),
                ])->save();
                $removalRequest->taiLieu->delete();
                LichSuThungRac::create([
                    'ma_tai_lieu' => $removalRequest->taiLieu->getKey(),
                    'ma_nguoi_thuc_hien' => $request->user()->getKey(),
                    'hanh_dong' => 'SOFT_DELETE',
                    'nguon' => 'TAC_GIA',
                    'ly_do' => $removalRequest->ly_do,
                ]);
            }

            // Làm mới bản ghi kèm quan hệ tài liệu (kể cả bản ghi đã xóa mềm withTrashed) để chuẩn bị gửi thông báo
            return $removalRequest->fresh([
                'nguoiYeuCau',
                'taiLieu' => fn($query) => $query->withTrashed(),
            ]);
        });

        // 3. Gửi thông báo kết quả cho tác giả (Thực hiện ngoài Transaction để tránh làm treo DB connection)
        $removalRequest->nguoiYeuCau?->notify(new DocumentRemovalRequestResolvedNotification(
            $removalRequest->load([
                'nguoiYeuCau',
                'taiLieu' => fn($query) => $query->withTrashed(),
            ])
        ));

        return back()->with('success', $data['action'] === 'APPROVE'
            ? 'Đã duyệt yêu cầu và gỡ mềm tài liệu.'
            : 'Đã từ chối yêu cầu gỡ tài liệu.');
    }
}
