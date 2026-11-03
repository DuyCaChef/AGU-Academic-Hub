<?php

namespace App\Modules\AcademicCatalog\Application\Actions;

use App\Models\Khoa;
use App\Modules\AcademicCatalog\Infrastructure\Persistence\AcademicCatalogRepository;
use App\Modules\Administration\Domain\Enums\HanhDongKiemToan;
use App\Modules\Administration\Domain\Enums\LoaiDoiTuongKiemToan;
use App\Modules\Administration\Domain\Services\AuditLogger;

class ManageFacultyAction
{
    public function __construct(
        protected AcademicCatalogRepository $repository
    ) {}

    // Thêm mới một Khoa vào danh mục đào tạo
    public function store(array $validatedData): Khoa
    {
        $faculty = Khoa::create($validatedData);

        // Ghi log của việc thêm mới một khoa
        app(AuditLogger::class)->log(
            actor: auth()->user(),
            action: HanhDongKiemToan::THAY_DOI_KHOA,
            targetType: LoaiDoiTuongKiemToan::KHOA,
            targetId: (string) $faculty->ma_khoa,
            description: "Thêm mới Khoa \"{$faculty->ten_khoa}\".",
            oldData: null,
            newData: $validatedData
        );

        return $faculty;
    }

    // Cập nhật thông tin Khoa hiện có (cho phép cập nhật cả Khoa đang bị vô hiệu hóa)
    public function update(int $maKhoa, array $validatedData): Khoa
    {
        $faculty = $this->repository->findFaculty($maKhoa);
        $oldData = $faculty->only(['ten_khoa', 'mo_ta']);
        $faculty->update($validatedData);

        // Ghi log của việc cập nhật một khoa
        app(AuditLogger::class)->log(
            actor: auth()->user(),
            action: HanhDongKiemToan::THAY_DOI_KHOA,
            targetType: LoaiDoiTuongKiemToan::KHOA,
            targetId: (string) $maKhoa,
            description: "Cập nhật thông tin Khoa \"{$faculty->ten_khoa}\".",
            oldData: $oldData,
            newData: $validatedData
        );

        return $faculty;
    }

    // Chuyển đổi trạng thái vô hiệu hóa hoặc kích hoạt lại Khoa (Soft Delete)
    public function toggle(int $maKhoa): array
    {
        $faculty = $this->repository->findFaculty($maKhoa);
        $wasTrashed = $faculty->trashed();
        $wasTrashed ? $faculty->restore() : $faculty->delete();

        $message = $faculty->trashed() ? 'Đã vô hiệu hóa khoa.' : 'Đã kích hoạt lại khoa.';

        // Ghi log của việc thay đổi trạng thái một khoam (vô hiệu hóa hoặc kích hoạt)
        app(AuditLogger::class)->log(
            actor: auth()->user(),
            action: HanhDongKiemToan::THAY_DOI_KHOA,
            targetType: LoaiDoiTuongKiemToan::KHOA,
            targetId: (string) $maKhoa,
            description: "Thay đổi trạng thái Khoa \"{$faculty->ten_khoa}\": {$message}",
            oldData: ['trashed' => $wasTrashed],
            newData: ['trashed' => $faculty->trashed()]
        );

        return [
            'success' => true,
            'trashed' => $faculty->trashed(),
            'message' => $message,
        ];
    }
}

