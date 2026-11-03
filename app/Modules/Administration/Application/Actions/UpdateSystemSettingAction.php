<?php

namespace App\Modules\Administration\Application\Actions;

use App\Models\CauHinhHeThong;
use App\Models\NguoiDung;
use App\Modules\Administration\Domain\Enums\HanhDongKiemToan;
use App\Modules\Administration\Domain\Enums\LoaiDoiTuongKiemToan;
use App\Modules\Administration\Infrastructure\Persistence\SystemSettingRepository;
use DomainException;

class UpdateSystemSettingAction
{
    public function __construct(
        protected SystemSettingRepository $repository,
        protected RecordAuditLogAction $auditAction
    ) {}

    public function execute(string $key, mixed $newValue, NguoiDung $actor): CauHinhHeThong
    {
        $setting = $this->repository->findByKey($key);

        if (!$setting) {
            throw new DomainException("Không tìm thấy cấu hình với khóa {$key}.");
        }

        $oldValue = $setting->gia_tri;

        // Cập nhật giá trị
        $this->repository->updateValue($setting, $newValue);

        // Ghi nhận nhật ký kiểm toán bắt buộc
        $this->auditAction->execute(
            actor: $actor,
            action: HanhDongKiemToan::THAY_DOI_CAU_HINH,
            targetType: LoaiDoiTuongKiemToan::CAU_HINH_HE_THONG,
            targetId: $key,
            description: "Cập nhật cấu hình \"{$setting->tieu_de}\" từ \"{$oldValue}\" thành \"{$newValue}\".",
            oldData: ['khoa' => $key, 'gia_tri' => $oldValue],
            newData: ['khoa' => $key, 'gia_tri' => (string) $newValue]
        );

        return $setting;
    }
}

