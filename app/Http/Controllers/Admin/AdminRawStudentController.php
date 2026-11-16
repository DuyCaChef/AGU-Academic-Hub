<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportRawStudentsRequest;
use App\Http\Requests\Admin\UpdateRawStudentRequest;
use App\Models\Khoa;
use App\Services\Admin\Actions\DeleteRawStudentAction;
use App\Services\Admin\Actions\ImportRawStudentsAction;
use App\Services\Admin\Actions\UpdateRawStudentAction;
use App\Services\Admin\Queries\GetRawStudentsQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminRawStudentController extends Controller
{
    /**
     * Màn hình danh sách dữ liệu sinh viên thô (Raw Roster).
     */
    public function index(Request $request, GetRawStudentsQuery $query): Response
    {
        abort_unless($request->user()->laQuanTriVien(), 403, 'Bạn không có quyền quản lý danh sách sinh viên thô.');

        $data = $query->execute($request->only(['search', 'da_kich_hoat', 'ma_khoa']));
        $data['faculties'] = Khoa::query()->select('ma_khoa', 'ten_khoa')->get();

        return Inertia::render('Admin/RawStudents/Index', $data);
    }

    /**
     * Nạp dữ liệu sinh viên thô hàng loạt (Import Raw Students).
     */
    public function import(ImportRawStudentsRequest $request, ImportRawStudentsAction $action): RedirectResponse
    {
        abort_unless($request->user()->laQuanTriVien(), 403, 'Bạn không có quyền nạp danh sách sinh viên.');

        $result = $action->execute($request->input('records'), $request->user());

        $message = "Đã nạp thành công {$result['total']} sinh viên ({$result['created']} thêm mới, {$result['updated']} cập nhật).";
        $warning = null;
        if (!empty($result['errors']) || !empty($result['duplicates'])) {
            $errCount = count($result['errors']);
            $dupCount = count($result['duplicates']);
            $warning = "Phát hiện {$errCount} bản ghi lỗi và {$dupCount} bản ghi trùng lặp trong quá trình nạp.";
        }

        $redirect = back()->with('success', $message);
        if ($warning) {
            $redirect->with('warning', $warning);
        }

        return $redirect;
    }

    /**
     * Cập nhật thông tin bản ghi sinh viên thô.
     */
    public function update(
        UpdateRawStudentRequest $request,
        int $id,
        UpdateRawStudentAction $action
    ): RedirectResponse {
        abort_unless($request->user()->laQuanTriVien(), 403, 'Bạn không có quyền sửa dữ liệu sinh viên thô.');

        $data = $request->validated();

        $action->execute($id, $data, $request->user());

        return back()->with('success', 'Đã cập nhật dữ liệu sinh viên thô.');
    }

    /**
     * Xóa bản ghi sinh viên thô.
     */
    public function destroy(
        Request $request,
        int $id,
        DeleteRawStudentAction $action
    ): RedirectResponse {
        abort_unless($request->user()->laQuanTriVien(), 403, 'Bạn không có quyền xóa dữ liệu sinh viên thô.');

        $action->execute($id, $request->user());

        return back()->with('success', 'Đã xóa bản ghi sinh viên thô.');
    }
}

