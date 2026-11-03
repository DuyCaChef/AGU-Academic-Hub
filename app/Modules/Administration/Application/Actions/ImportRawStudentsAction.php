<?php

namespace App\Modules\Administration\Application\Actions;

use App\Models\NguoiDung;
use App\Modules\Administration\Domain\Enums\HanhDongKiemToan;
use App\Modules\Administration\Domain\Enums\LoaiDoiTuongKiemToan;
use App\Modules\Administration\Infrastructure\Persistence\RawStudentRepository;

class ImportRawStudentsAction
{
    public function __construct(
        protected RawStudentRepository $repository,
        protected RecordAuditLogAction $auditAction
    ) {}

    public function execute(array $records, NguoiDung $actor): array
    {
        $result = $this->repository->importBatch($records);

        // Ghi nhật ký kiểm toán hành vi nạp dữ liệu sinh viên thô
        $this->auditAction->execute(
            actor: $actor,
            action: HanhDongKiemToan::NAP_SINH_VIEN_RAW,
            targetType: LoaiDoiTuongKiemToan::SINH_VIEN_RAW,
            targetId: 'BATCH_IMPORT',
            description: "Nạp dữ liệu danh sách sinh viên thô: {$result['created']} thêm mới, {$result['updated']} cập nhật.",
            oldData: null,
            newData: [
                'so_luong_tao_moi' => $result['created'],
                'so_luong_cap_nhat' => $result['updated'],
                'tong_so_xu_ly' => $result['total'],
            ]
        );

        return $result;
    }
}

