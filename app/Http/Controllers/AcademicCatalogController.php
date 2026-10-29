<?php

namespace App\Http\Controllers;

use App\Models\ChuyenNganh;
use App\Models\Khoa;
use App\Models\MonHoc;
use App\Http\Requests\Admin\StoreCourseRequest;
use App\Http\Requests\Admin\StoreFacultyRequest;
use App\Http\Requests\Admin\StoreMajorRequest;
use App\Http\Requests\Admin\UpdateCourseRequest;
use App\Http\Requests\Admin\UpdateFacultyRequest;
use App\Http\Requests\Admin\UpdateMajorRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AcademicCatalogController extends Controller
{
    // Hiển thị trang quản trị danh mục kèm dữ liệu Khoa, Chuyên ngành và Môn học.
    // Hàm áp dụng đồng thời tìm kiếm, lọc trạng thái, lọc theo Khoa và phân trang độc lập cho từng bảng.
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));
        $maKhoa = $request->integer('ma_khoa') ?: null;
        $trangThai = $request->input('trang_thai', 'active');

        // Lấy danh sách Khoa và đếm số Chuyên ngành/Môn học trực thuộc.
        $faculties = $this->applyStatus(Khoa::query(), $trangThai)
            ->when($search, fn(Builder $query) => $query->where(function (Builder $query) use ($search): void {
                $query->where('ky_hieu_khoa', 'like', "%{$search}%")
                    ->orWhere('ten_khoa', 'like', "%{$search}%");
            }))
            ->withCount(['chuyenNganhs', 'monHocs'])
            ->orderBy('ten_khoa')
            ->paginate(10, ['*'], 'faculties_page')
            ->withQueryString();

        // Lấy Chuyên ngành, đồng thời nạp thông tin Khoa để hiển thị tên Khoa.
        $majors = $this->applyEffectiveChildStatus(ChuyenNganh::query(), $trangThai, 'chuyen_nganh')
            ->with(['khoa' => fn($query) => $query->withTrashed()->select('ma_khoa', 'ten_khoa', 'ky_hieu_khoa')])
            ->when($maKhoa, fn(Builder $query) => $query->where('ma_khoa', $maKhoa))
            ->when($search, fn(Builder $query) => $query->where(function (Builder $query) use ($search): void {
                $query->where('ky_hieu_nganh', 'like', "%{$search}%")
                    ->orWhere('ten_chuyen_nganh', 'like', "%{$search}%");
            }))
            ->orderBy('ten_chuyen_nganh')
            ->paginate(10, ['*'], 'majors_page')
            ->withQueryString();

        // Lấy Môn học cùng Khoa và các Chuyên ngành được gán.
        $courses = $this->applyEffectiveChildStatus(MonHoc::query(), $trangThai, 'mon_hoc')
            ->with([
                'khoa' => fn($query) => $query->withTrashed()->select('ma_khoa', 'ten_khoa', 'ky_hieu_khoa'),
                'chuyenNganhs:ma_chuyen_nganh,ky_hieu_nganh,ten_chuyen_nganh',
            ])
            ->when($maKhoa, fn(Builder $query) => $query->where('ma_khoa', $maKhoa))
            ->when($search, fn(Builder $query) => $query->where(function (Builder $query) use ($search): void {
                $query->where('ma_hoc_phan', 'like', "%{$search}%")
                    ->orWhere('ten_mon_hoc', 'like', "%{$search}%");
            }))
            ->orderBy('ten_mon_hoc')
            ->paginate(10, ['*'], 'courses_page')
            ->withQueryString();

        // Trả dữ liệu sang trang Inertia và cung cấp các lựa chọn cho biểu mẫu.
        return Inertia::render('Admin/AcademicCatalog/Index', [
            'faculties' => $faculties,
            'majors' => $majors,
            'courses' => $courses,
            'facultyOptions' => Khoa::query()->orderBy('ten_khoa')->get(['ma_khoa', 'ky_hieu_khoa', 'ten_khoa']),
            'majorOptions' => ChuyenNganh::query()
                ->with('khoa:ma_khoa,ky_hieu_khoa,ten_khoa')
                ->orderBy('ten_chuyen_nganh')
                ->get(['ma_chuyen_nganh', 'ma_khoa', 'ky_hieu_nganh', 'ten_chuyen_nganh']),
            'filters' => [
                'search' => $search,
                'ma_khoa' => $maKhoa,
                'trang_thai' => $trangThai,
            ],
        ]);
    }


    // Thêm mới một khoa
    public function storeFaculty(StoreFacultyRequest $request): RedirectResponse
    {
        // Kiểm tra dữ liệu rồi tạo Khoa mới; mã Khoa phải là duy nhất.
        Khoa::create($request->validated());

        return back()->with('success', 'Đã thêm khoa.');
    }

    // Cập nhật khoa đã tồn tại
    public function updateFaculty(UpdateFacultyRequest $request, int $maKhoa): RedirectResponse
    {
        // Cho phép sửa cả bản ghi đã vô hiệu hóa nhưng vẫn giữ nguyên mã định danh.
        $faculty = Khoa::withTrashed()->findOrFail($maKhoa);
        $faculty->update($request->validated());

        return back()->with('success', 'Đã cập nhật khoa.');
    }

    // Vô hiệu hóa hoặc khôi phục Khoa mà không xóa dữ liệu vật lý.
    public function toggleFaculty(int $maKhoa): RedirectResponse
    {
        // Chuyển đổi giữa trạng thái hoạt động và vô hiệu hóa bằng soft delete.
        $faculty = Khoa::withTrashed()->findOrFail($maKhoa);
        $faculty->trashed() ? $faculty->restore() : $faculty->delete();

        return back()->with('success', $faculty->trashed() ? 'Đã vô hiệu hóa khoa.' : 'Đã kích hoạt lại khoa.');
    }

    // Thêm mới một chuyên ngành
    public function storeMajor(StoreMajorRequest $request): RedirectResponse
    {
        // Tạo Chuyên ngành thuộc một Khoa đang hoạt động.
        ChuyenNganh::create($request->validated());

        return back()->with('success', 'Đã thêm chuyên ngành.');
    }

    // Cập nhật thông tin chuyên ngành
    public function updateMajor(UpdateMajorRequest $request, int $maChuyenNganh): RedirectResponse
    {
        // Cập nhật Chuyên ngành và cho phép đổi sang Khoa khác đang hoạt động.
        $major = ChuyenNganh::withTrashed()->findOrFail($maChuyenNganh);
        $major->update($request->validated());

        return back()->with('success', 'Đã cập nhật chuyên ngành.');
    }

    // Vô hiệu hóa hoặc khôi phục Chuyên ngành mà không xóa dữ liệu vật lý.
    public function toggleMajor(int $maChuyenNganh): RedirectResponse
    {
        // Vô hiệu hóa hoặc khôi phục Chuyên ngành mà không xóa dữ liệu vật lý.
        $major = ChuyenNganh::withTrashed()->findOrFail($maChuyenNganh);

        // Không cho kích hoạt riêng ngành khi Khoa cha vẫn đang vô hiệu hóa.
        if ($major->trashed() && $major->khoa()->withTrashed()->whereNotNull('ngay_xoa_tam')->exists()) {
            return back()->withErrors(['major' => 'Không thể kích hoạt chuyên ngành khi Khoa đang bị vô hiệu hóa.']);
        }

        $major->trashed() ? $major->restore() : $major->delete();

        return back()->with('success', $major->trashed() ? 'Đã vô hiệu hóa chuyên ngành.' : 'Đã kích hoạt lại chuyên ngành.');
    }

    // Thêm mới một môn học
    public function storeCourse(StoreCourseRequest $request): RedirectResponse
    {
        // Tạo Môn học, sau đó gán các Chuyên ngành hợp lệ cùng Khoa.
        $data = $request->validated();
        $majorIds = $data['ma_chuyen_nganh'] ?? [];
        unset($data['ma_chuyen_nganh']);

        $course = MonHoc::create($data);
        $course->chuyenNganhs()->sync($majorIds);

        return back()->with('success', 'Đã thêm môn học.');
    }

    // Cập nhật thông tin môn học
    public function updateCourse(UpdateCourseRequest $request, int $maMonHoc): RedirectResponse
    {
        // Cập nhật thông tin Môn học và đồng bộ lại danh sách Chuyên ngành.
        $course = MonHoc::withTrashed()->findOrFail($maMonHoc);
        $data = $request->validated();
        $majorIds = $data['ma_chuyen_nganh'] ?? [];
        unset($data['ma_chuyen_nganh']);

        $course->update($data);
        $course->chuyenNganhs()->sync($majorIds);

        return back()->with('success', 'Đã cập nhật môn học.');
    }

    // Vô hiệu hóa hoặc khôi phục Môn học mà không xóa dữ liệu vật lý.
    public function toggleCourse(int $maMonHoc): RedirectResponse
    {
        // Chỉ cho vô hiệu hóa Môn học khi không còn dữ liệu liên kết quan trọng.
        $course = MonHoc::withTrashed()->findOrFail($maMonHoc);

        // Môn học chỉ được kích hoạt khi Khoa cha đang hoạt động.
        if ($course->trashed() && $course->khoa()->withTrashed()->whereNull('ngay_xoa_tam')->doesntExist()) {
            return back()->withErrors(['course' => 'Không thể kích hoạt môn học khi Khoa đang bị vô hiệu hóa.']);
        }

        if (!$course->trashed() && $this->hasCourseLinks($course)) {
            return back()->withErrors(['course' => 'Không thể vô hiệu hóa môn học đang có tài liệu, review hoặc yêu cầu tài liệu liên kết.']);
        }

        $course->trashed() ? $course->restore() : $course->delete();

        return back()->with('success', $course->trashed() ? 'Đã vô hiệu hóa môn học.' : 'Đã kích hoạt lại môn học.');
    }

    // Các phương thức phụ trợ để kiểm tra dữ liệu và áp dụng các quy tắc.
    // Kiểm tra xem Môn học có liên kết với Tài liệu, Review hoặc Yêu cầu tài liệu hay không.
    private function hasCourseLinks(MonHoc $course): bool
    {
        // Kiểm tra cả bản ghi đang hoạt động và đã soft delete để không mất lịch sử.
        return $course->taiLieus()->withTrashed()->exists()
            || $course->danhGiaMonHocs()->withTrashed()->exists()
            || $course->yeuCauTaiLieus()->withTrashed()->exists();
    }

    // Áp dụng trạng thái cho truy vấn.
    private function applyStatus(Builder $query, string $status): Builder
    {
        // Chọn phạm vi dữ liệu: đang hoạt động, đã vô hiệu hóa hoặc tất cả.
        return match ($status) {
            'inactive' => $query->onlyTrashed(),
            'all' => $query->withTrashed(),
            default => $query,
        };
    }

    // Lọc trạng thái hiệu lực thực tế của Chuyên ngành/Môn học.
    private function applyEffectiveChildStatus(Builder $query, string $status, string $table): Builder
    {
        return match ($status) {
            'active' => $this->applyStatus($query, $status)->whereHas('khoa'),
            'inactive' => $query->withTrashed()->where(function (Builder $query) use ($table): void {
                    $query->whereNotNull("{$table}.ngay_xoa_tam")
                    ->orWhereHas('khoa', fn(Builder $faculty) => $faculty
                        ->withTrashed()
                        ->whereNotNull('khoa.ngay_xoa_tam'));
                }),
            default => $query->withTrashed(),
        };
    }
}
