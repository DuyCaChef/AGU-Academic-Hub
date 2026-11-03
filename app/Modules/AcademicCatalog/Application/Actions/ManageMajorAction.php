<?php

namespace App\Modules\AcademicCatalog\Application\Actions;

use App\Models\ChuyenNganh;
use App\Modules\AcademicCatalog\Domain\Services\AcademicCatalogRuleEvaluator;
use App\Modules\AcademicCatalog\Infrastructure\Persistence\AcademicCatalogRepository;
use App\Modules\Administration\Domain\Enums\HanhDongKiemToan;
use App\Modules\Administration\Domain\Enums\LoaiDoiTuongKiemToan;
use App\Modules\Administration\Domain\Services\AuditLogger;

class ManageMajorAction
{
    public function __construct(
        protected AcademicCatalogRepository $repository,
        protected AcademicCatalogRuleEvaluator $evaluator
    ) {}

    // Thêm mới một Chuyên ngành trực thuộc Khoa đang hoạt động
    public function store(array $validatedData): ChuyenNganh
    {
        $major = ChuyenNganh::create($validatedData);

        // Ghi log thêm mới một ngành
        app(AuditLogger::class)->log(
            actor: auth()->user(),
            action: HanhDongKiemToan::THAY_DOI_CHUYEN_NGANH,
            targetType: LoaiDoiTuongKiemToan::CHUYEN_NGANH,
            targetId: (string) $major->ma_chuyen_nganh,
            description: "Thêm mới Chuyên ngành \"{$major->ten_chuyen_nganh}\".",
            oldData: null,
            newData: $validatedData
        );

        return $major;
    }

    // Cập nhật thông tin Chuyên ngành (cho phép đổi sang Khoa khác đang hoạt động)
    public function update(int $maChuyenNganh, array $validatedData): ChuyenNganh
    {
        $major = $this->repository->findMajor($maChuyenNganh);
        $oldData = $major->only(['ten_chuyen_nganh', 'ma_khoa', 'mo_ta']);
        $major->update($validatedData);

        // Ghi log của việc cập nhật một ngành
        app(AuditLogger::class)->log(
            actor: auth()->user(),
            action: HanhDongKiemToan::THAY_DOI_CHUYEN_NGANH,
            targetType: LoaiDoiTuongKiemToan::CHUYEN_NGANH,
            targetId: (string) $maChuyenNganh,
            description: "Cập nhật thông tin Chuyên ngành \"{$major->ten_chuyen_nganh}\".",
            oldData: $oldData,
            newData: $validatedData
        );

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

        $wasTrashed = $major->trashed();
        $wasTrashed ? $major->restore() : $major->delete();

        $message = $major->trashed() ? 'Đã vô hiệu hóa chuyên ngành.' : 'Đã kích hoạt lại chuyên ngành.';

        // Ghi log thay đổi trạng thái của một ngành
        app(AuditLogger::class)->log(
            actor: auth()->user(),
            action: HanhDongKiemToan::THAY_DOI_CHUYEN_NGANH,
            targetType: LoaiDoiTuongKiemToan::CHUYEN_NGANH,
            targetId: (string) $maChuyenNganh,
            description: "Thay đổi trạng thái Chuyên ngành \"{$major->ten_chuyen_nganh}\": {$message}",
            oldData: ['trashed' => $wasTrashed],
            newData: ['trashed' => $major->trashed()]
        );

        return [
            'success' => true,
            'trashed' => $major->trashed(),
            'message' => $message,
        ];
    }
}

