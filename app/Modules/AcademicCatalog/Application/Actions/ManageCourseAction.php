<?php

namespace App\Modules\AcademicCatalog\Application\Actions;

use App\Models\MonHoc;
use App\Modules\AcademicCatalog\Domain\Services\AcademicCatalogRuleEvaluator;
use App\Modules\AcademicCatalog\Infrastructure\Persistence\AcademicCatalogRepository;
use App\Modules\Administration\Domain\Enums\HanhDongKiemToan;
use App\Modules\Administration\Domain\Enums\LoaiDoiTuongKiemToan;
use App\Modules\Administration\Domain\Services\AuditLogger;

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


        // Ghi log của việc thêm mới một môn học
        app(AuditLogger::class)->log(
            actor: auth()->user(),
            action: HanhDongKiemToan::THAY_DOI_MON_HOC,
            targetType: LoaiDoiTuongKiemToan::MON_HOC,
            targetId: (string) $course->ma_mon_hoc,
            description: "Thêm mới Môn học \"{$course->ten_mon_hoc}\" ({$course->ma_hoc_phan}).",
            oldData: null,
            newData: array_merge($validatedData, ['chuyen_nganh_ids' => $majorIds])
        );

        return $course;
    }

    // Cập nhật thông tin Môn học và cập nhật liên kết Chuyên ngành
    public function update(int $maMonHoc, array $validatedData): MonHoc
    {
        $course = $this->repository->findCourse($maMonHoc);
        $majorIds = $validatedData['ma_chuyen_nganh'] ?? [];
        unset($validatedData['ma_chuyen_nganh']);

        $oldData = $course->only(['ten_mon_hoc', 'ma_hoc_phan', 'so_tin_chi', 'ma_khoa']);
        $course->update($validatedData);
        $course->chuyenNganhs()->sync($majorIds);

        // Ghi log của việc cập nhật môn học
        app(AuditLogger::class)->log(
            actor: auth()->user(),
            action: HanhDongKiemToan::THAY_DOI_MON_HOC,
            targetType: LoaiDoiTuongKiemToan::MON_HOC,
            targetId: (string) $maMonHoc,
            description: "Cập nhật thông tin Môn học \"{$course->ten_mon_hoc}\" ({$course->ma_hoc_phan}).",
            oldData: $oldData,
            newData: array_merge($validatedData, ['chuyen_nganh_ids' => $majorIds])
        );

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

        $wasTrashed = $course->trashed();
        $wasTrashed ? $course->restore() : $course->delete();

        $message = $course->trashed() ? 'Đã vô hiệu hóa môn học.' : 'Đã kích hoạt lại môn học.';

        // Ghi log của việc thay đổi trạng thái môn học
        app(AuditLogger::class)->log(
            actor: auth()->user(),
            action: HanhDongKiemToan::THAY_DOI_MON_HOC,
            targetType: LoaiDoiTuongKiemToan::MON_HOC,
            targetId: (string) $maMonHoc,
            description: "Thay đổi trạng thái Môn học \"{$course->ten_mon_hoc}\": {$message}",
            oldData: ['trashed' => $wasTrashed],
            newData: ['trashed' => $course->trashed()]
        );

        return [
            'success' => true,
            'trashed' => $course->trashed(),
            'message' => $message,
        ];
    }
}

