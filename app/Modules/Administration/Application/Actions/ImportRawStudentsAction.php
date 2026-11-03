<?php

namespace App\Modules\Administration\Application\Actions;

use App\Enums\VaiTroNguoiDung;
use App\Models\NguoiDung;
use App\Modules\Administration\Domain\Enums\HanhDongKiemToan;
use App\Modules\Administration\Domain\Enums\LoaiDoiTuongKiemToan;
use App\Modules\Administration\Infrastructure\Notifications\StudentRosterImportedNotification;
use App\Modules\Administration\Infrastructure\Notifications\StudentRosterImportWarningNotification;
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
                'so_loi' => count($result['errors']),
                'so_trung' => count($result['duplicates']),
            ]
        );

        $staff = NguoiDung::query()
            ->whereIn('vai_tro', [VaiTroNguoiDung::QUAN_TRI_VIEN, VaiTroNguoiDung::KIEM_DUYET_VIEN])
            ->get();

        // 1. Thông báo có dữ liệu sinh viên mới được nạp khi có bản ghi hợp lệ
        if ($result['total'] > 0) {
            $notification = new StudentRosterImportedNotification($result['total'], $result['created'], $result['updated']);
            foreach ($staff as $user) {
                $user->notify($notification);
            }
        }

        // 2. Thông báo có lỗi hoặc bản ghi trùng trong quá trình import
        $errorCount = count($result['errors']);
        $duplicateCount = count($result['duplicates']);
        if ($errorCount > 0 || $duplicateCount > 0) {
            $warningNotification = new StudentRosterImportWarningNotification(
                $errorCount,
                $duplicateCount,
                $result['errors'],
                $result['duplicates']
            );
            foreach ($staff as $user) {
                $user->notify($warningNotification);
            }
        }

        return $result;
    }
}

