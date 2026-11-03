<?php

namespace App\Modules\Administration\Application\Actions;

use App\Models\NguoiDung;
use App\Modules\Administration\Domain\Enums\HanhDongKiemToan;
use App\Modules\Administration\Domain\Enums\LoaiDoiTuongKiemToan;
use App\Modules\Administration\Infrastructure\Persistence\RawStudentRepository;
use DomainException;

class DeleteRawStudentAction
{
    public function __construct(
        protected RawStudentRepository $repository,
        protected RecordAuditLogAction $auditAction
    ) {}

    public function execute(int $id, NguoiDung $actor): bool
    {
        $student = $this->repository->findById($id);

        if (!$student) {
            throw new DomainException('Không tìm thấy bản ghi sinh viên thô.');
        }

        $oldData = [
            'mssv'         => $student->mssv,
            'ho_ten'       => $student->ho_ten,
            'email_sv'     => $student->email_sv,
            'ma_khoa'      => $student->ma_khoa,
            'da_kich_hoat' => $student->da_kich_hoat,
        ];

        $mssv = $student->mssv;
        $name = $student->ho_ten;

        $this->repository->delete($student);

        // Ghi nhật ký kiểm toán
        $this->auditAction->execute(
            actor: $actor,
            action: HanhDongKiemToan::XOA_SINH_VIEN_RAW,
            targetType: LoaiDoiTuongKiemToan::SINH_VIEN_RAW,
            targetId: $mssv,
            description: "Xóa dữ liệu sinh viên thô MSSV {$mssv} ({$name}).",
            oldData: $oldData,
            newData: null
        );

        return true;
    }
}

