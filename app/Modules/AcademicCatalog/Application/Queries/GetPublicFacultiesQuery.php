<?php

namespace App\Modules\AcademicCatalog\Application\Queries;

use App\Models\NguoiDung;
use App\Modules\AcademicCatalog\Infrastructure\Persistence\AcademicCatalogRepository;
use Illuminate\Database\Eloquent\Collection;

class GetPublicFacultiesQuery
{
    public function __construct(
        protected AcademicCatalogRepository $repository
    ) {}

    // Lấy danh sách Khoa đang hoạt động và sắp xếp ưu tiên Khoa của người dùng đăng nhập
    public function execute(?NguoiDung $user = null): Collection
    {
        $faculties = $this->repository->getActiveFacultiesWithCourses();

        if ($user && $user->ma_khoa) {
            return $faculties->sortBy(function ($faculty) use ($user) {
                return $faculty->ma_khoa === $user->ma_khoa ? 0 : 1;
            })->values();
        }

        return $faculties;
    }

    // Lấy danh sách môn học công khai trực thuộc một khoa
    public function getCoursesByFacultyId(int $facultyId): Collection
    {
        return $this->repository->getActiveCoursesByFacultyId($facultyId);
    }

    // Lấy danh sách môn học được sử dụng bởi một chuyên ngành
    public function getCoursesByMajorId(int $majorId): Collection
    {
        return $this->repository->getActiveCoursesByMajorId($majorId);
    }
}
