<?php

namespace App\Modules\AcademicCatalog\Application\Actions;

use App\Models\MonHoc;
use App\Modules\AcademicCatalog\Domain\Services\AcademicCatalogRuleEvaluator;
use App\Modules\AcademicCatalog\Infrastructure\Persistence\AcademicCatalogRepository;

class ManageCourseAction
{
    public function __construct(
        protected AcademicCatalogRepository $repository,
        protected AcademicCatalogRuleEvaluator $evaluator
    ) {}

    // Thêm mới một Môn học và đồng bộ danh sách Chuyên ngành trực thuộc
    public function store(array $validatedData): MonHoc
    {
        $majorIds = $validatedData['ma_chuyen_nganh'] ?? [];
        unset($validatedData['ma_chuyen_nganh']);

        $course = MonHoc::create($validatedData);
        $course->chuyenNganhs()->sync($majorIds);

        return $course;
    }

    // Cập nhật thông tin Môn học và cập nhật liên kết Chuyên ngành
    public function update(int $maMonHoc, array $validatedData): MonHoc
    {
        $course = $this->repository->findCourse($maMonHoc);
        $majorIds = $validatedData['ma_chuyen_nganh'] ?? [];
        unset($validatedData['ma_chuyen_nganh']);

        $course->update($validatedData);
        $course->chuyenNganhs()->sync($majorIds);

        return $course;
    }

    // Vô hiệu hóa hoặc kích hoạt lại Môn học (kiểm tra trạng thái Khoa cha và dữ liệu liên kết)
    public function toggle(int $maMonHoc): array
    {
        $course = $this->repository->findCourse($maMonHoc);
        $hasLinks = $this->repository->hasCourseLinks($course);

        $error = $this->evaluator->validateCourseToggle($course, $hasLinks);
        if ($error !== null) {
            return [
                'success' => false,
                'error_field' => 'course',
                'message' => $error,
            ];
        }

        $course->trashed() ? $course->restore() : $course->delete();

        $message = $course->trashed() ? 'Đã vô hiệu hóa môn học.' : 'Đã kích hoạt lại môn học.';

        return [
            'success' => true,
            'trashed' => $course->trashed(),
            'message' => $message,
        ];
    }
}

