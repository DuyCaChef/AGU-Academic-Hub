<?php

namespace App\Modules\Moderation\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Moderation\Application\Actions\ArchiveReviewReportAction;
use App\Modules\Moderation\Application\Actions\BulkArchiveReportsAction;
use App\Modules\Moderation\Application\Actions\BulkDismissReportsAction;
use App\Modules\Moderation\Application\Actions\ResolveReviewReportAction;
use App\Modules\Moderation\Application\Queries\GetReviewReportDetailQuery;
use App\Modules\Moderation\Application\Queries\GetReviewReportQueueQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ReviewModerationController extends Controller
{
    // Hàng đợi xem danh sách báo cáo đánh giá môn học cần xử lý
    public function index(Request $request, GetReviewReportQueueQuery $query): Response
    {
        abort_unless(
            $request->user()?->laQuanTriVien() || $request->user()?->laKiemDuyetVien(),
            403,
            'Bạn không có quyền thực hiện kiểm duyệt đánh giá.'
        );

        $data = $query->execute($request->only(['type', 'status', 'reason', 'from', 'to', 'search']));

        return Inertia::render('Admin/ReviewReports/Index', $data);
    }

    // Xem chi tiết hồ sơ một báo cáo đánh giá và các báo cáo liên quan
    public function show(Request $request, int $id, GetReviewReportDetailQuery $query): Response
    {
        abort_unless(
            $request->user()?->laQuanTriVien() || $request->user()?->laKiemDuyetVien(),
            403,
            'Bạn không có quyền thực hiện kiểm duyệt đánh giá.'
        );

        $data = $query->execute($id);

        return Inertia::render('Admin/ReviewReports/Show', $data);
    }

    // Quyết định xử lý báo cáo đánh giá: Tạm ẩn, Xóa bài hoặc Bác bỏ báo cáo
    public function resolve(Request $request, int $id, ResolveReviewReportAction $action): RedirectResponse
    {
        abort_unless(
            $request->user()?->laQuanTriVien() || $request->user()?->laKiemDuyetVien(),
            403,
            'Bạn không có quyền thực hiện kiểm duyệt đánh giá.'
        );

        $data = $request->validate([
            'action'            => ['required', Rule::in(['HIDE', 'DELETE', 'DISMISS'])],
            'resolution_reason' => ['required', 'string', 'max:2000'],
        ]);

        $result = $action->execute($id, $data['action'], $data['resolution_reason'], $request->user());

        return back()->with('success', "Đã {$result} bài đánh giá và ghi nhận kết quả xử lý.");
    }

    // Lưu trữ báo cáo đã xử lý hoặc bác bỏ để dọn sạch hàng đợi
    public function archive(Request $request, int $id, ArchiveReviewReportAction $action): RedirectResponse
    {
        abort_unless(
            $request->user()?->laQuanTriVien() || $request->user()?->laKiemDuyetVien(),
            403,
            'Bạn không có quyền thực hiện kiểm duyệt đánh giá.'
        );

        $action->execute($id);

        return back()->with('success', 'Đã lưu trữ báo cáo đánh giá khỏi hàng đợi.');
    }

    // Bác bỏ hàng loạt báo cáo đã chọn (hỗ trợ cả tài liệu và đánh giá)
    public function bulkDismiss(Request $request, BulkDismissReportsAction $action): RedirectResponse
    {
        abort_unless(
            $request->user()?->laQuanTriVien() || $request->user()?->laKiemDuyetVien(),
            403,
            'Bạn không có quyền thực hiện kiểm duyệt đánh giá.'
        );

        $data = $request->validate([
            'reports'           => ['required', 'array', 'min:1'],
            'reports.*.id'     => ['required', 'integer'],
            'reports.*.type'   => ['required', 'string', 'in:MON_HOC,GIANG_VIEN,TAI_LIEU'],
            'resolution_reason' => ['nullable', 'string', 'max:2000'],
        ], [
            'reports.required' => 'Vui lòng chọn ít nhất một báo cáo để thao tác.',
            'reports.min'      => 'Vui lòng chọn ít nhất một báo cáo để thao tác.',
        ]);

        $reason = $data['resolution_reason'] ?: 'Bác bỏ hàng loạt do nội dung không vi phạm tiêu chuẩn cộng đồng.';
        $result = $action->execute($data['reports'], $reason, $request->user());

        if ($result['dismissed_count'] === 0) {
            return back()->with('error', 'Không thể bác bỏ các báo cáo đã chọn.');
        }

        return back()->with('success', "Đã bác bỏ thành công {$result['dismissed_count']} báo cáo vi phạm.");
    }

    // Lưu trữ hàng loạt báo cáo đã chọn khỏi hàng đợi
    public function bulkArchive(Request $request, BulkArchiveReportsAction $action): RedirectResponse
    {
        abort_unless(
            $request->user()?->laQuanTriVien() || $request->user()?->laKiemDuyetVien(),
            403,
            'Bạn không có quyền thực hiện kiểm duyệt đánh giá.'
        );

        $data = $request->validate([
            'reports'         => ['required', 'array', 'min:1'],
            'reports.*.id'   => ['required', 'integer'],
            'reports.*.type' => ['required', 'string', 'in:MON_HOC,GIANG_VIEN,TAI_LIEU'],
        ], [
            'reports.required' => 'Vui lòng chọn ít nhất một báo cáo để lưu trữ.',
            'reports.min'      => 'Vui lòng chọn ít nhất một báo cáo để lưu trữ.',
        ]);

        $result = $action->execute($data['reports']);

        if ($result['archived_count'] === 0) {
            return back()->with('error', 'Không thể lưu trữ các báo cáo đã chọn.');
        }

        return back()->with('success', "Đã lưu trữ thành công {$result['archived_count']} báo cáo khỏi hàng đợi.");
    }
}

