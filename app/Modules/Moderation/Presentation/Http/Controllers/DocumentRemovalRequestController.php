<?php

namespace App\Modules\Moderation\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Moderation\Application\Actions\DecideRemovalRequestAction;
use App\Modules\Moderation\Application\Queries\GetRemovalRequestDetailQuery;
use App\Modules\Moderation\Application\Queries\GetRemovalRequestsQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
    public function decide(Request $request, int $id, DecideRemovalRequestAction $action): RedirectResponse
    {
        abort_unless($request->user()?->laQuanTriVien() || $request->user()?->laKiemDuyetVien(), 403, 'Bạn không có quyền xử lý yêu cầu gỡ tài liệu.');

        $data = $request->validate([
            'action'          => ['required', Rule::in(['APPROVE', 'REJECT'])],
            'decision_reason' => ['nullable', 'required_if:action,REJECT', 'string', 'max:2000'],
        ]);

        $action->execute($id, $data['action'], $data['decision_reason'] ?? null, $request->user());

        return back()->with(
            'success',
            $data['action'] === 'APPROVE'
                ? 'Đã duyệt yêu cầu và gỡ mềm tài liệu.'
                : 'Đã từ chối yêu cầu gỡ tài liệu.'
        );
    }
}

