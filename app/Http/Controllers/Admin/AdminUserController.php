<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RecordUserViolationRequest;
use App\Http\Requests\Admin\UpdateUserPermissionsRequest;
use App\Http\Requests\Admin\UserReasonRequest;
use App\Services\Admin\Actions\RecordUserViolationAction;
use App\Services\Admin\Actions\ToggleUserStatusAction;
use App\Services\Admin\Actions\UpdateUserPermissionsAction;
use App\Services\Admin\Queries\GetUserDetailQuery;
use App\Services\Admin\Queries\GetUsersManagementListQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminUserController extends Controller
{
    // Danh sách người dùng và các bộ lọc quản trị
    public function index(Request $request, GetUsersManagementListQuery $query): Response
    {
        abort_unless($request->user()->laQuanTriVien(), 403, 'Bạn không có quyền truy cập trang quản lý người dùng.');

        $data = $query->execute($request->only(['search', 'vai_tro', 'trang_thai', 'ma_khoa']));

        return Inertia::render('Admin/Users/Index', $data);
    }

    // Xem hồ sơ chi tiết của tài khoản kèm lịch sử trạng thái và phân quyền
    public function show(Request $request, int $ma_nguoi_dung, GetUserDetailQuery $query): Response
    {
        abort_unless($request->user()->laQuanTriVien(), 403, 'Bạn không có quyền quản trị tài khoản.');

        $data = $query->execute($ma_nguoi_dung, $request->user());

        return Inertia::render('Admin/Users/Show', $data);
    }

    // Cập nhật vai trò phân quyền (RBAC) và khoa của người dùng
    public function updatePermissions(
        UpdateUserPermissionsRequest $request,
        int $ma_nguoi_dung,
        UpdateUserPermissionsAction $action
    ): RedirectResponse {
        abort_unless($request->user()->laQuanTriVien(), 403, 'Bạn không có quyền quản trị tài khoản.');

        $data = $request->validated();

        $result = $action->execute($request->user(), $ma_nguoi_dung, $data);

        if ($result['type'] === 'validation_error') {
            return back()->withErrors([$result['field'] => $result['message']])->withInput();
        }

        return back()->with($result['type'], $result['message']);
    }

    // Đảo chiều trạng thái hoạt động của tài khoản (Khóa hoặc Mở khóa)
    public function toggleStatus(
        UserReasonRequest $request,
        int $ma_nguoi_dung,
        ToggleUserStatusAction $action
    ): RedirectResponse {
        abort_unless($request->user()->laQuanTriVien(), 403, 'Bạn không có quyền quản trị tài khoản.');

        $data = $request->validated();

        $result = $action->execute($request->user(), $ma_nguoi_dung, $data['ly_do'] ?? null);

        return back()->with($result['type'], $result['message']);
    }

    // Ghi nhận vi phạm của người dùng và tự động nâng cấp mức cảnh báo
    public function recordViolation(
        RecordUserViolationRequest $request,
        int $ma_nguoi_dung,
        RecordUserViolationAction $action
    ): RedirectResponse {
        abort_unless($request->user()->laQuanTriVien(), 403, 'Bạn không có quyền quản trị tài khoản.');

        $data = $request->validated();

        $result = $action->execute($request->user(), $ma_nguoi_dung, $data['ly_do']);

        return back()->with($result['type'], $result['message']);
    }
}

