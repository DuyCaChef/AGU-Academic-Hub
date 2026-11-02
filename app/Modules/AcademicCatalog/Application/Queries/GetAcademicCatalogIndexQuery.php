<?php

namespace App\Modules\AcademicCatalog\Application\Queries;

use App\Modules\AcademicCatalog\Infrastructure\Persistence\AcademicCatalogRepository;

class GetAcademicCatalogIndexQuery
{
    public function __construct(
        protected AcademicCatalogRepository $repository
    ) {}

    // Thu thập dữ liệu trang quản trị danh mục gồm Khoa, Chuyên ngành, Môn học và bộ lọc
    public function execute(array $inputFilters): array
    {
        $search = trim((string) ($inputFilters['search'] ?? ''));
        $maKhoa = !empty($inputFilters['ma_khoa']) ? (int) $inputFilters['ma_khoa'] : null;
        $trangThai = (string) ($inputFilters['trang_thai'] ?? 'active');

        $faculties = $this->repository->getPaginatedFaculties($search, $trangThai, 10);
        $majors = $this->repository->getPaginatedMajors($search, $maKhoa, $trangThai, 10);
        $courses = $this->repository->getPaginatedCourses($search, $maKhoa, $trangThai, 10);

        return [
            'faculties' => $faculties,
            'majors' => $majors,
            'courses' => $courses,
            'facultyOptions' => $this->repository->getFacultyOptions(),
            'majorOptions' => $this->repository->getMajorOptions(),
            'filters' => [
                'search' => $search,
                'ma_khoa' => $maKhoa,
                'trang_thai' => $trangThai,
            ],
        ];
    }
}

