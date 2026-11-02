<?php

namespace App\Modules\AcademicCatalog\Application\Queries;

use App\Models\Khoa;
use App\Models\MonHoc;
use App\Modules\AcademicCatalog\Infrastructure\Persistence\AcademicCatalogRepository;
use Illuminate\Database\Eloquent\Collection;

class GetPublicCoursesQuery
{
    public function __construct(
        protected AcademicCatalogRepository $repository
    ) {}

    // Lấy danh sách Khoa công khai phục vụ tra cứu môn học
    public function getPublicFaculties(): Collection
    {
        return Khoa::with('monHocs')->get();
    }

    // Lấy chi tiết môn học công khai theo Mã học phần
    public function findActiveCourseByCode(string $courseCode): MonHoc
    {
        return $this->repository->findActiveCourseByCode($courseCode);
    }
}
