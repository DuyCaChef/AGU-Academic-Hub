<?php

namespace App\Modules\Administration\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Khoa;
use App\Modules\Administration\Application\Actions\DeleteRawStudentAction;
use App\Modules\Administration\Application\Actions\ImportRawStudentsAction;
use App\Modules\Administration\Application\Actions\UpdateRawStudentAction;
use App\Modules\Administration\Application\Queries\GetRawStudentsQuery;
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
    public function import(Request $request, ImportRawStudentsAction $action): RedirectResponse
    {
        abort_unless($request->user()->laQuanTriVien(), 403, 'Bạn không có quyền nạp danh sách sinh viên.');

        $request->validate([
            'records' => ['required', 'array', 'min:1'],
            'records.*.mssv' => ['required', 'string', 'max:50'],
            'records.*.ho_ten' => ['required', 'string', 'max:255'],
            'records.*.email_sv' => ['nullable', 'email', 'max:191'],
            'records.*.ma_khoa' => ['nullable', 'string', 'max:20'],
        ]);

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
        Request $request,
        int $id,
        UpdateRawStudentAction $action
    ): RedirectResponse {
        abort_unless($request->user()->laQuanTriVien(), 403, 'Bạn không có quyền sửa dữ liệu sinh viên thô.');

        $data = $request->validate([
            'ho_ten' => ['required', 'string', 'max:255'],
            'email_sv' => ['nullable', 'email', 'max:191'],
            'ma_khoa' => ['nullable', 'string', 'max:20'],
            'mat_khau_mac_dinh' => ['nullable', 'string', 'min:6'],
        ]);

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

