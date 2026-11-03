<?php

namespace App\Modules\Administration\Application\Actions;

use App\Models\DanhSachSinhVienRaw;
use App\Models\NguoiDung;
use App\Modules\Administration\Domain\Enums\HanhDongKiemToan;
use App\Modules\Administration\Domain\Enums\LoaiDoiTuongKiemToan;
use App\Modules\Administration\Infrastructure\Persistence\RawStudentRepository;
use DomainException;

class UpdateRawStudentAction
{
    public function __construct(
        protected RawStudentRepository $repository,
        protected RecordAuditLogAction $auditAction
    ) {}

    public function execute(int $id, array $data, NguoiDung $actor): DanhSachSinhVienRaw
    {
        $student = $this->repository->findById($id);

        if (!$student) {
            throw new DomainException('Không tìm thấy bản ghi sinh viên thô.');
        }

        $oldData = [
            'mssv'     => $student->mssv,
            'ho_ten'   => $student->ho_ten,
            'email_sv' => $student->email_sv,
            'ma_khoa'  => $student->ma_khoa,
        ];

        $updated = $this->repository->update($student, $data);

        $newData = [
            'mssv'     => $updated->mssv,
            'ho_ten'   => $updated->ho_ten,
            'email_sv' => $updated->email_sv,
            'ma_khoa'  => $updated->ma_khoa,
        ];

        // Ghi nhật ký kiểm toán
        $this->auditAction->execute(
            actor: $actor,
            action: HanhDongKiemToan::CAP_NHAT_SINH_VIEN_RAW,
            targetType: LoaiDoiTuongKiemToan::SINH_VIEN_RAW,
            targetId: $student->mssv,
            description: "Cập nhật dữ liệu sinh viên thô MSSV {$student->mssv} ({$student->ho_ten}).",
            oldData: $oldData,
            newData: $newData
        );

        return $updated;
    }
}

