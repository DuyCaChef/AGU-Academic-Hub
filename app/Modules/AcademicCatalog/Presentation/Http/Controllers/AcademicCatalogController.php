<?php

namespace App\Modules\AcademicCatalog\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCourseRequest;
use App\Http\Requests\Admin\StoreFacultyRequest;
use App\Http\Requests\Admin\StoreMajorRequest;
use App\Http\Requests\Admin\UpdateCourseRequest;
use App\Http\Requests\Admin\UpdateFacultyRequest;
use App\Http\Requests\Admin\UpdateMajorRequest;
use App\Modules\AcademicCatalog\Application\Actions\ManageCourseAction;
use App\Modules\AcademicCatalog\Application\Actions\ManageFacultyAction;
use App\Modules\AcademicCatalog\Application\Actions\ManageMajorAction;
use App\Modules\AcademicCatalog\Application\Queries\GetAcademicCatalogIndexQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AcademicCatalogController extends Controller
{
    // Hiển thị trang quản trị danh mục đào tạo (Khoa, Chuyên ngành và Môn học)
    public function index(Request $request, GetAcademicCatalogIndexQuery $query): Response
    {
        abort_unless($request->user()?->laQuanTriVien(), 403, 'Bạn không có quyền quản trị danh mục học thuật.');

        $data = $query->execute($request->only(['search', 'ma_khoa', 'trang_thai']));

        return Inertia::render('Admin/AcademicCatalog/Index', $data);
    }

    // Thêm mới một Khoa vào danh mục
    public function storeFaculty(StoreFacultyRequest $request, ManageFacultyAction $action): RedirectResponse
    {
        $action->store($request->validated());

        return back()->with('success', 'Đã thêm khoa.');
    }

    // Cập nhật thông tin Khoa
    public function updateFaculty(UpdateFacultyRequest $request, int $maKhoa, ManageFacultyAction $action): RedirectResponse
    {
        $action->update($maKhoa, $request->validated());

        return back()->with('success', 'Đã cập nhật khoa.');
    }

    // Vô hiệu hóa hoặc khôi phục Khoa
    public function toggleFaculty(Request $request, int $maKhoa, ManageFacultyAction $action): RedirectResponse
    {
        abort_unless($request->user()?->laQuanTriVien(), 403, 'Bạn không có quyền quản trị danh mục học thuật.');

        $result = $action->toggle($maKhoa);

        return back()->with('success', $result['message']);
    }

    // Thêm mới một Chuyên ngành
    public function storeMajor(StoreMajorRequest $request, ManageMajorAction $action): RedirectResponse
    {
        $action->store($request->validated());

        return back()->with('success', 'Đã thêm chuyên ngành.');
    }

    // Cập nhật thông tin Chuyên ngành
    public function updateMajor(UpdateMajorRequest $request, int $maChuyenNganh, ManageMajorAction $action): RedirectResponse
    {
        $action->update($maChuyenNganh, $request->validated());

        return back()->with('success', 'Đã cập nhật chuyên ngành.');
    }

    // Vô hiệu hóa hoặc khôi phục Chuyên ngành
    public function toggleMajor(Request $request, int $maChuyenNganh, ManageMajorAction $action): RedirectResponse
    {
        abort_unless($request->user()?->laQuanTriVien(), 403, 'Bạn không có quyền quản trị danh mục học thuật.');

        $result = $action->toggle($maChuyenNganh);

        if (!$result['success']) {
            return back()->withErrors([$result['error_field'] => $result['message']]);
        }

        return back()->with('success', $result['message']);
    }

    // Thêm mới một Môn học và đồng bộ Chuyên ngành
    public function storeCourse(StoreCourseRequest $request, ManageCourseAction $action): RedirectResponse
    {
        $action->store($request->validated());

        return back()->with('success', 'Đã thêm môn học.');
    }

    // Cập nhật thông tin Môn học và đồng bộ Chuyên ngành
    public function updateCourse(UpdateCourseRequest $request, int $maMonHoc, ManageCourseAction $action): RedirectResponse
    {
        $action->update($maMonHoc, $request->validated());

        return back()->with('success', 'Đã cập nhật môn học.');
    }

    // Vô hiệu hóa hoặc khôi phục Môn học
    public function toggleCourse(Request $request, int $maMonHoc, ManageCourseAction $action): RedirectResponse
    {
        abort_unless($request->user()?->laQuanTriVien(), 403, 'Bạn không có quyền quản trị danh mục học thuật.');

        $result = $action->toggle($maMonHoc);

        if (!$result['success']) {
            return back()->withErrors([$result['error_field'] => $result['message']]);
        }

        return back()->with('success', $result['message']);
    }
}

