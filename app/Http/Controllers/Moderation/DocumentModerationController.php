<?php

namespace App\Http\Controllers\Moderation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Moderation\BulkApproveDocumentsRequest;
use App\Http\Requests\Moderation\DocumentReviewDecisionRequest;
use App\Http\Requests\Moderation\RejectDocumentRequest;
use App\Http\Requests\Moderation\ReportResolutionRequest;
use App\Models\TaiLieu;
use App\Services\Moderation\Actions\ApproveNewDocumentAction;
use App\Services\Moderation\Actions\ArchiveViolationReportAction;
use App\Services\Moderation\Actions\BulkApproveNewDocumentsAction;
use App\Services\Moderation\Actions\DecideDocumentReviewAction;
use App\Services\Moderation\Actions\PermanentlyDeleteDocumentAction;
use App\Services\Moderation\Actions\RejectNewDocumentAction;
use App\Services\Moderation\Actions\ResolveViolationReportAction;
use App\Services\Moderation\Actions\RestoreDocumentAction;
use App\Services\Moderation\Queries\GetDeletedDocumentsQuery;
use App\Services\Moderation\Queries\GetReportDetailQuery;
use App\Services\Moderation\Queries\GetReviewDetailQuery;
use App\Services\Moderation\Queries\GetReviewQueueQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class DocumentModerationController extends Controller
{
    // Chuyển hướng hàng đợi báo cáo tài liệu cũ về Hàng đợi Báo cáo Thống nhất (/quan-tri/bao-cao-danh-gia?type=TAI_LIEU)
    public function indexReports(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->laQuanTriVien() || $request->user()?->laKiemDuyetVien(), 403, 'Bạn không có quyền thực hiện kiểm duyệt.');

        return redirect()->route('admin.review-reports.index', ['type' => 'TAI_LIEU']);
    }

    // Hàng đợi kiểm tra riêng biệt dành cho các tài liệu bị gắn cờ nguy cơ cao
    public function reviewQueue(Request $request, GetReviewQueueQuery $query): Response
    {
        abort_unless($request->user()?->laQuanTriVien() || $request->user()?->laKiemDuyetVien(), 403, 'Bạn không có quyền thực hiện kiểm duyệt.');

        $data = $query->execute($request->only(['tab', 'status']));

        return Inertia::render('Admin/Documents/ReviewQueue', $data);
    }

    // Phê duyệt công khai tài liệu mới đăng
    public function approveNewDocument(Request $request, int $id, ApproveNewDocumentAction $action): RedirectResponse
    {
        abort_unless($request->user()?->laQuanTriVien() || $request->user()?->laKiemDuyetVien(), 403, 'Bạn không có quyền thực hiện kiểm duyệt.');

        $result = $action->execute($id, $request->user());

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        return back()->with('success', $result['message']);
    }

    // Phê duyệt hàng loạt tài liệu mới đăng
    public function bulkApproveNewDocuments(
        BulkApproveDocumentsRequest $request,
        BulkApproveNewDocumentsAction $action
    ): RedirectResponse {
        abort_unless($request->user()?->laQuanTriVien() || $request->user()?->laKiemDuyetVien(), 403, 'Bạn không có quyền thực hiện kiểm duyệt.');

        $data = $request->validated();

        $result = $action->execute($data['ids'], $request->user());

        if ($result['approved_count'] === 0) {
            return back()->with('error', 'Không có tài liệu nào đủ điều kiện phê duyệt.');
        }

        $msg = "Đã phê duyệt thành công {$result['approved_count']} tài liệu.";
        if ($result['failed_count'] > 0) {
            $msg .= " (Bỏ qua {$result['failed_count']} tài liệu không đủ điều kiện).";
        }

        return back()->with('success', $msg);
    }

    // Từ chối duyệt tài liệu mới đăng kèm lý do
    public function rejectNewDocument(RejectDocumentRequest $request, int $id, RejectNewDocumentAction $action): RedirectResponse
    {
        abort_unless($request->user()?->laQuanTriVien() || $request->user()?->laKiemDuyetVien(), 403, 'Bạn không có quyền thực hiện kiểm duyệt.');

        $result = $action->execute($id, $request->input('reason'), $request->user());

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        return back()->with('success', $result['message']);
    }

    // Chi tiết thẩm định tài liệu trong hàng đợi kiểm tra (hỗ trợ cả trashed)
    public function reviewDocument(Request $request, int $id, GetReviewDetailQuery $query): Response
    {
        abort_unless($request->user()?->laQuanTriVien() || $request->user()?->laKiemDuyetVien(), 403, 'Bạn không có quyền thực hiện kiểm duyệt.');

        $data = $query->execute($id);

        return Inertia::render('Admin/Documents/ReviewDetail', $data);
    }

    // Xem trước nội dung tệp trực tuyến từ ổ đĩa nội bộ (Protected Inline Stream)
    public function previewDocument(Request $request, int $id): HttpResponse
    {
        abort_unless($request->user()?->laQuanTriVien() || $request->user()?->laKiemDuyetVien(), 403, 'Bạn không có quyền thực hiện kiểm duyệt.');

        $document = TaiLieu::withTrashed()->findOrFail($id);
        $path = $document->duong_dan_tep;

        abort_unless(Storage::disk('local')->exists($path), 404, 'Không tìm thấy tệp tài liệu.');

        return Storage::disk('local')->response($path, basename($path), [
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
        ]);
    }

    // Quyết định xử lý tài liệu trong hàng đợi kiểm tra
    public function decideDocument(DocumentReviewDecisionRequest $request, int $id, DecideDocumentReviewAction $action): RedirectResponse
    {
        abort_unless($request->user()?->laQuanTriVien() || $request->user()?->laKiemDuyetVien(), 403, 'Bạn không có quyền thực hiện kiểm duyệt.');

        $data = $request->validated();

        $action->execute($id, $data['action'], $data['resolution_reason'], $request->user());

        return back()->with('success', 'Đã ghi nhận quyết định kiểm tra tài liệu.');
    }

    // Xem chi tiết một bản ghi báo cáo cụ thể và danh sách báo cáo liên quan cùng tài liệu
    public function showReport(Request $request, int $id, GetReportDetailQuery $query): Response
    {
        abort_unless($request->user()?->laQuanTriVien() || $request->user()?->laKiemDuyetVien(), 403, 'Bạn không có quyền thực hiện kiểm duyệt.');

        $data = $query->execute($id);

        return Inertia::render('Admin/Reports/Show', $data);
    }

    // Quyết định xử lý báo cáo: Duyệt ẩn/xóa tài liệu hoặc Bác bỏ báo cáo
    public function resolveReport(ReportResolutionRequest $request, int $id, ResolveViolationReportAction $action): RedirectResponse
    {
        abort_unless($request->user()?->laQuanTriVien() || $request->user()?->laKiemDuyetVien(), 403, 'Bạn không có quyền thực hiện kiểm duyệt.');

        $data = $request->validated();

        $result = $action->execute($id, $data['action'], $data['resolution_reason'], $request->user());

        return back()->with('success', "Đã {$result} tài liệu và ghi nhận kết quả xử lý.");
    }

    // Xóa mềm (Archive) bản ghi báo cáo để dọn sạch hàng đợi
    public function archiveReport(Request $request, int $id, ArchiveViolationReportAction $action): RedirectResponse
    {
        abort_unless($request->user()?->laQuanTriVien() || $request->user()?->laKiemDuyetVien(), 403, 'Bạn không có quyền thực hiện kiểm duyệt.');

        $action->execute($id);

        return back()->with('success', 'Đã lưu trữ báo cáo khỏi hàng đợi.');
    }

    // Danh sách tài liệu trong thùng rác (Soft Deleted)
    public function deletedDocuments(Request $request, GetDeletedDocumentsQuery $query): Response
    {
        abort_unless($request->user()?->laQuanTriVien() || $request->user()?->laKiemDuyetVien(), 403, 'Bạn không có quyền xem thùng rác tài liệu.');

        $data = $query->execute(
            $request->only(['from', 'to', 'deleted_by', 'source', 'status']),
            $request->user()
        );

        return Inertia::render('Admin/Documents/Deleted', $data);
    }

    // Khôi phục tài liệu đã xóa mềm (Chỉ dành riêng cho Quản trị viên - Admin)
    public function restoreDocument(Request $request, int $id, RestoreDocumentAction $action): RedirectResponse
    {
        abort_unless($request->user()?->laQuanTriVien(), 403, 'Chỉ Quản trị viên mới có quyền khôi phục tài liệu.');

        $document = $action->execute(
            $id,
            $request->boolean('confirm_violation'),
            $request->input('reason'),
            $request->user()
        );

        return back()->with('success', "Đã khôi phục tài liệu \"{$document->tieu_de}\" thành công.");
    }

    // Xóa vĩnh viễn (Hard Delete / Purge) tài liệu và file vật lý khỏi hệ thống (Chỉ dành riêng cho Admin)
    public function permanentlyDeleteDocument(
        Request $request,
        int $id,
        PermanentlyDeleteDocumentAction $action
    ): RedirectResponse {
        abort_unless($request->user()?->laQuanTriVien(), 403, 'Chỉ Quản trị viên mới có quyền xóa vĩnh viễn tài liệu.');

        $action->execute($id, $request->input('reason'), $request->user());

        return back()->with('success', 'Đã xóa vĩnh viễn tài liệu và tệp vật lý.');
    }
}

