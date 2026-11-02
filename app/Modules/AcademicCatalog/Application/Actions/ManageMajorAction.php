<?php

namespace App\Modules\AcademicCatalog\Application\Actions;

use App\Models\ChuyenNganh;
use App\Modules\AcademicCatalog\Domain\Services\AcademicCatalogRuleEvaluator;
use App\Modules\AcademicCatalog\Infrastructure\Persistence\AcademicCatalogRepository;

class ManageMajorAction
{
    public function __construct(
        protected AcademicCatalogRepository $repository,
        protected AcademicCatalogRuleEvaluator $evaluator
    ) {}

    // Thêm mới một Chuyên ngành trực thuộc Khoa đang hoạt động
    public function store(array $validatedData): ChuyenNganh
    {
        return ChuyenNganh::create($validatedData);
    }

    // Cập nhật thông tin Chuyên ngành (cho phép đổi sang Khoa khác đang hoạt động)
    public function update(int $maChuyenNganh, array $validatedData): ChuyenNganh
    {
        $major = $this->repository->findMajor($maChuyenNganh);
        $major->update($validatedData);

        return $major;
    }

    // Vô hiệu hóa hoặc kích hoạt lại Chuyên ngành (kèm kiểm tra tính hợp lệ của Khoa cha)
    public function toggle(int $maChuyenNganh): array
    {
        $major = $this->repository->findMajor($maChuyenNganh);

        $error = $this->evaluator->validateMajorToggle($major);
        if ($error !== null) {
            return [
                'success' => false,
                'error_field' => 'major',
                'message' => $error,
            ];
        }

        $major->trashed() ? $major->restore() : $major->delete();

        $message = $major->trashed() ? 'Đã vô hiệu hóa chuyên ngành.' : 'Đã kích hoạt lại chuyên ngành.';

        return [
            'success' => true,
            'trashed' => $major->trashed(),
            'message' => $message,
        ];
    }
}

