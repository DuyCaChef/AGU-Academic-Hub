<?php

namespace App\Modules\AcademicCatalog\Infrastructure\Persistence;

use App\Models\ChuyenNganh;
use App\Models\Khoa;
use App\Models\MonHoc;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class AcademicCatalogRepository
{
    // Lấy danh sách Khoa phân trang kèm bộ lọc và đếm chuyên ngành/môn học
    public function getPaginatedFaculties(?string $search, string $status, int $perPage = 10): LengthAwarePaginator
    {
        return $this->applyStatus(Khoa::query(), $status)
            ->when($search, fn(Builder $query) => $query->where(function (Builder $query) use ($search): void {
                $query->where('ky_hieu_khoa', 'like', "%{$search}%")
                    ->orWhere('ten_khoa', 'like', "%{$search}%");
            }))
            ->withCount(['chuyenNganhs', 'monHocs'])
            ->orderBy('ten_khoa')
            ->paginate($perPage, ['*'], 'faculties_page')
            ->withQueryString();
    }

    // Lấy danh sách Chuyên ngành phân trang theo trạng thái hiệu lực thực tế
    public function getPaginatedMajors(?string $search, ?int $maKhoa, string $status, int $perPage = 10): LengthAwarePaginator
    {
        return $this->applyEffectiveChildStatus(ChuyenNganh::query(), $status, 'chuyen_nganh')
            ->with(['khoa' => fn($query) => $query->withTrashed()->select('ma_khoa', 'ten_khoa', 'ky_hieu_khoa')])
            ->when($maKhoa, fn(Builder $query) => $query->where('ma_khoa', $maKhoa))
            ->when($search, fn(Builder $query) => $query->where(function (Builder $query) use ($search): void {
                $query->where('ky_hieu_nganh', 'like', "%{$search}%")
                    ->orWhere('ten_chuyen_nganh', 'like', "%{$search}%");
            }))
            ->orderBy('ten_chuyen_nganh')
            ->paginate($perPage, ['*'], 'majors_page')
            ->withQueryString();
    }

    // Lấy danh sách Môn học phân trang kèm khoa và danh sách chuyên ngành
    public function getPaginatedCourses(?string $search, ?int $maKhoa, string $status, int $perPage = 10): LengthAwarePaginator
    {
        return $this->applyEffectiveChildStatus(MonHoc::query(), $status, 'mon_hoc')
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
            ->paginate($perPage, ['*'], 'courses_page')
            ->withQueryString();
    }

    // Lấy danh sách Khoa cho thẻ lựa chọn (select options)
    public function getFacultyOptions(): Collection
    {
        return Khoa::query()->orderBy('ten_khoa')->get(['ma_khoa', 'ky_hieu_khoa', 'ten_khoa']);
    }

    // Lấy danh sách Chuyên ngành cho thẻ lựa chọn (select options)
    public function getMajorOptions(): Collection
    {
        return ChuyenNganh::query()
            ->with('khoa:ma_khoa,ky_hieu_khoa,ten_khoa')
            ->orderBy('ten_chuyen_nganh')
            ->get(['ma_chuyen_nganh', 'ma_khoa', 'ky_hieu_nganh', 'ten_chuyen_nganh']);
    }

    // Tìm kiếm Khoa theo ID bao gồm cả bản ghi đã xóa mềm
    public function findFaculty(int $maKhoa): Khoa
    {
        return Khoa::withTrashed()->findOrFail($maKhoa);
    }

    // Tìm kiếm Chuyên ngành theo ID bao gồm cả bản ghi đã xóa mềm
    public function findMajor(int $maChuyenNganh): ChuyenNganh
    {
        return ChuyenNganh::withTrashed()->findOrFail($maChuyenNganh);
    }

    // Tìm kiếm Môn học theo ID bao gồm cả bản ghi đã xóa mềm
    public function findCourse(int $maMonHoc): MonHoc
    {
        return MonHoc::withTrashed()->findOrFail($maMonHoc);
    }

    // Lấy danh sách Khoa đang hoạt động kèm các môn học trực thuộc còn hiệu lực
    public function getActiveFacultiesWithCourses(): Collection
    {
        return Khoa::query()
            ->select(['ma_khoa', 'ky_hieu_khoa', 'ten_khoa'])
            ->with([
                'monHocs' => fn($query) => $query
                    ->select(['ma_mon_hoc', 'ma_khoa', 'ma_hoc_phan', 'ten_mon_hoc', 'so_tin_chi'])
                    ->orderBy('ten_mon_hoc'),
            ])
            ->orderBy('ten_khoa')
            ->get();
    }

    // Lấy danh sách môn học công khai thuộc một Khoa còn hiệu lực
    public function getActiveCoursesByFacultyId(int $facultyId): Collection
    {
        return MonHoc::query()
            ->where('ma_khoa', $facultyId)
            ->whereHas('khoa')
            ->with(['chuyenNganhs:ma_chuyen_nganh,ky_hieu_nganh,ten_chuyen_nganh'])
            ->orderBy('ten_mon_hoc')
            ->get();
    }

    // Lấy danh sách môn học được sử dụng bởi một Chuyên ngành (quan hệ nhiều-nhiều)
    public function getActiveCoursesByMajorId(int $majorId): Collection
    {
        return MonHoc::query()
            ->whereHas('khoa')
            ->whereHas('chuyenNganhs', fn(Builder $query) => $query
                ->where('chuyen_nganh.ma_chuyen_nganh', $majorId)
                ->whereHas('khoa')
            )
            ->with([
                'khoa:ma_khoa,ky_hieu_khoa,ten_khoa',
                'chuyenNganhs:ma_chuyen_nganh,ky_hieu_nganh,ten_chuyen_nganh',
            ])
            ->orderBy('ten_mon_hoc')
            ->get();
    }

    // Lấy danh sách môn học công khai phân trang có bộ lọc tìm kiếm, khoa và chuyên ngành
    public function getPublicPaginatedCourses(?string $search = null, ?int $maKhoa = null, ?int $maChuyenNganh = null, int $perPage = 12): LengthAwarePaginator
    {
        return MonHoc::query()
            ->whereHas('khoa')
            ->when($maKhoa, fn(Builder $query) => $query->where('ma_khoa', $maKhoa))
            ->when($maChuyenNganh, fn(Builder $query) => $query->whereHas('chuyenNganhs', fn(Builder $majorQuery) => $majorQuery->where('chuyen_nganh.ma_chuyen_nganh', $maChuyenNganh)))
            ->when($search, fn(Builder $query) => $query->where(function (Builder $subQuery) use ($search): void {
                $subQuery->where('ma_hoc_phan', 'like', "%{$search}%")
                    ->orWhere('ten_mon_hoc', 'like', "%{$search}%");
            }))
            ->with([
                'khoa:ma_khoa,ky_hieu_khoa,ten_khoa',
                'chuyenNganhs:ma_chuyen_nganh,ky_hieu_nganh,ten_chuyen_nganh',
            ])
            ->orderBy('ten_mon_hoc')
            ->paginate($perPage)
            ->withQueryString();
    }

    // Tìm môn học theo Mã học phần công khai (thuộc Khoa còn hoạt động)
    public function findActiveCourseByCode(string $courseCode): MonHoc
    {
        return MonHoc::query()
            ->where(function (Builder $query) use ($courseCode): void {
                $query->where('ma_hoc_phan', $courseCode);
                if (is_numeric($courseCode)) {
                    $query->orWhere('ma_mon_hoc', (int) $courseCode);
                }
            })
            ->whereHas('khoa')
            ->with([
                'khoa:ma_khoa,ky_hieu_khoa,ten_khoa',
                'chuyenNganhs:ma_chuyen_nganh,ky_hieu_nganh,ten_chuyen_nganh',
            ])
            ->firstOrFail();
    }

    // Kiểm tra xem Môn học có liên kết với tài liệu, review hoặc yêu cầu tài liệu không
    public function hasCourseLinks(MonHoc $course): bool
    {
        return $course->taiLieus()->withTrashed()->exists()
            || $course->danhGiaMonHocs()->withTrashed()->exists()
            || $course->yeuCauTaiLieus()->withTrashed()->exists();
    }

    // Áp dụng trạng thái xóa mềm cho truy vấn
    public function applyStatus(Builder $query, string $status): Builder
    {
        return match ($status) {
            'inactive' => $query->onlyTrashed(),
            'all' => $query->withTrashed(),
            default => $query,
        };
    }

    // Áp dụng trạng thái hiệu lực thực tế của phần tử con (phụ thuộc vào Khoa cha)
    public function applyEffectiveChildStatus(Builder $query, string $status, string $table): Builder
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

