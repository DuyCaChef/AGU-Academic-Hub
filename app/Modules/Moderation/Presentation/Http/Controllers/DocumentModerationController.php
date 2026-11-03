<?php

namespace App\Modules\Moderation\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\TaiLieu;
use App\Modules\Moderation\Application\Actions\ArchiveViolationReportAction;
use App\Modules\Moderation\Application\Actions\DecideDocumentReviewAction;
use App\Modules\Moderation\Application\Actions\PermanentlyDeleteDocumentAction;
use App\Modules\Moderation\Application\Actions\ResolveViolationReportAction;
use App\Modules\Moderation\Application\Actions\RestoreDocumentAction;
use App\Modules\Moderation\Application\Queries\GetDeletedDocumentsQuery;
use App\Modules\Moderation\Application\Queries\GetReportDetailQuery;
use App\Modules\Moderation\Application\Queries\GetReportQueueQuery;
use App\Modules\Moderation\Application\Queries\GetReviewDetailQuery;
use App\Modules\Moderation\Application\Queries\GetReviewQueueQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class DocumentModerationController extends Controller
{
    // Hàng đợi xem danh sách báo cáo vi phạm cần xử lý
    public function indexReports(Request $request, GetReportQueueQuery $query): Response
    {
        abort_unless($request->user()?->laQuanTriVien() || $request->user()?->laKiemDuyetVien(), 403, 'Bạn không có quyền thực hiện kiểm duyệt.');

        $data = $query->execute($request->only(['status', 'reason', 'from', 'to']));

        return Inertia::render('Admin/Reports/Index', $data);
    }

    // Hàng đợi kiểm tra riêng biệt dành cho các tài liệu bị gắn cờ nguy cơ cao
    public function reviewQueue(Request $request, GetReviewQueueQuery $query): Response
    {
        abort_unless($request->user()?->laQuanTriVien() || $request->user()?->laKiemDuyetVien(), 403, 'Bạn không có quyền thực hiện kiểm duyệt.');

        $data = $query->execute();

        return Inertia::render('Admin/Documents/ReviewQueue', $data);
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
    public function decideDocument(Request $request, int $id, DecideDocumentReviewAction $action): RedirectResponse
    {
        abort_unless($request->user()?->laQuanTriVien() || $request->user()?->laKiemDuyetVien(), 403, 'Bạn không có quyền thực hiện kiểm duyệt.');

        $data = $request->validate([
            'action'            => ['required', Rule::in(['KEEP', 'RESTORE', 'HIDE', 'DELETE'])],
            'resolution_reason' => ['required', 'string', 'max:2000'],
        ]);

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
    public function resolveReport(Request $request, int $id, ResolveViolationReportAction $action): RedirectResponse
    {
        abort_unless($request->user()?->laQuanTriVien() || $request->user()?->laKiemDuyetVien(), 403, 'Bạn không có quyền thực hiện kiểm duyệt.');

        $data = $request->validate([
            'action'            => ['required', Rule::in(['HIDE', 'DELETE', 'DISMISS'])],
            'resolution_reason' => ['required', 'string', 'max:2000'],
        ]);

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

