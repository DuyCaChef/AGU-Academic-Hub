<?php

namespace App\Http\Controllers\Moderation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Moderation\RemovalDecisionRequest;
use App\Services\Moderation\Actions\DecideRemovalRequestAction;
use App\Services\Moderation\Queries\GetRemovalRequestDetailQuery;
use App\Services\Moderation\Queries\GetRemovalRequestsQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DocumentRemovalRequestController extends Controller
{
    // Hiển thị danh sách hàng đợi yêu cầu gỡ bỏ tài liệu
    public function index(Request $request, GetRemovalRequestsQuery $query): Response
    {
        abort_unless($request->user()?->laQuanTriVien() || $request->user()?->laKiemDuyetVien(), 403, 'Bạn không có quyền xử lý yêu cầu gỡ tài liệu.');

        $data = $query->execute($request->only('status'));

        return Inertia::render('Admin/Documents/RemovalRequests/Index', $data);
    }

    // Xem hồ sơ chi tiết một yêu cầu xin gỡ tài liệu
    public function show(Request $request, int $id, GetRemovalRequestDetailQuery $query): Response
    {
        abort_unless($request->user()?->laQuanTriVien() || $request->user()?->laKiemDuyetVien(), 403, 'Bạn không có quyền xử lý yêu cầu gỡ tài liệu.');

        $data = $query->execute($id);

        return Inertia::render('Admin/Documents/RemovalRequests/Show', $data);
    }

    // Quyết định duyệt hay từ chối một yêu cầu gỡ bỏ tài liệu
    public function decide(RemovalDecisionRequest $request, int $id, DecideRemovalRequestAction $action): RedirectResponse
    {
        abort_unless($request->user()?->laQuanTriVien() || $request->user()?->laKiemDuyetVien(), 403, 'Bạn không có quyền xử lý yêu cầu gỡ tài liệu.');

        $data = $request->validated();

        $action->execute($id, $data['action'], $data['decision_reason'] ?? null, $request->user());

        return back()->with(
            'success',
            $data['action'] === 'APPROVE'
                ? 'Đã duyệt yêu cầu và gỡ mềm tài liệu.'
                : 'Đã từ chối yêu cầu gỡ tài liệu.'
        );
    }
}

