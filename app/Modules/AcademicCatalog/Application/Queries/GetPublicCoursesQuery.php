<?php

namespace App\Modules\AcademicCatalog\Application\Queries;

use App\Models\MonHoc;
use App\Models\NguoiDung;
use App\Modules\AcademicCatalog\Infrastructure\Persistence\AcademicCatalogRepository;
use App\Modules\Documents\Infrastructure\Persistence\DocumentRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class GetPublicCoursesQuery
{
    public function __construct(
        protected AcademicCatalogRepository $repository,
        protected GetPublicFacultiesQuery $facultiesQuery,
        protected DocumentRepository $documentRepository
    ) {}

    // Lấy danh sách Khoa công khai ưu tiên khoa của sinh viên
    public function getPublicFaculties(?NguoiDung $user = null): Collection
    {
        return $this->facultiesQuery->execute($user);
    }

    // Lấy chi tiết môn học công khai theo Mã học phần
    public function findActiveCourseByCode(string $courseCode): MonHoc
    {
        return $this->repository->findActiveCourseByCode($courseCode);
    }

    // Lấy danh sách môn học công khai phân trang kèm bộ lọc
    public function getPaginatedCourses(array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        $search = !empty($filters['search']) ? trim((string) $filters['search']) : null;
        $maKhoa = !empty($filters['ma_khoa']) ? (int) $filters['ma_khoa'] : null;
        $maChuyenNganh = !empty($filters['ma_chuyen_nganh']) ? (int) $filters['ma_chuyen_nganh'] : null;

        return $this->repository->getPublicPaginatedCourses($search, $maKhoa, $maChuyenNganh, $perPage);
    }

    // Lấy danh sách tài liệu công khai phân trang phục vụ tra cứu
    public function getPaginatedDocuments(array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        return $this->documentRepository->paginatePublicDocuments($filters, $perPage);
    }
}
