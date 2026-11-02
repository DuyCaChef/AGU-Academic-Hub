<?php

namespace App\Modules\AcademicCatalog\Application\Actions;

use App\Models\Khoa;
use App\Modules\AcademicCatalog\Infrastructure\Persistence\AcademicCatalogRepository;

class ManageFacultyAction
{
    public function __construct(
        protected AcademicCatalogRepository $repository
    ) {}

    // Thêm mới một Khoa vào danh mục đào tạo
    public function store(array $validatedData): Khoa
    {
        return Khoa::create($validatedData);
    }

    // Cập nhật thông tin Khoa hiện có (cho phép cập nhật cả Khoa đang bị vô hiệu hóa)
    public function update(int $maKhoa, array $validatedData): Khoa
    {
        $faculty = $this->repository->findFaculty($maKhoa);
        $faculty->update($validatedData);

        return $faculty;
    }

    // Chuyển đổi trạng thái vô hiệu hóa hoặc kích hoạt lại Khoa (Soft Delete)
    public function toggle(int $maKhoa): array
    {
        $faculty = $this->repository->findFaculty($maKhoa);
        $faculty->trashed() ? $faculty->restore() : $faculty->delete();

        $message = $faculty->trashed() ? 'Đã vô hiệu hóa khoa.' : 'Đã kích hoạt lại khoa.';

        return [
            'success' => true,
            'trashed' => $faculty->trashed(),
            'message' => $message,
        ];
    }
}

