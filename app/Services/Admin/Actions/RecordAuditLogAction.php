<?php

namespace App\Services\Admin\Actions;

use App\Models\NguoiDung;
use App\Models\NhatKyKiemToan;
use App\Enums\HanhDongKiemToan;
use App\Enums\LoaiDoiTuongKiemToan;
use App\Services\Admin\AuditLogger;

class RecordAuditLogAction
{
    public function __construct(
        protected AuditLogger $logger
    ) {}

    public function execute(
        NguoiDung|int|null $actor,
        HanhDongKiemToan|string $action,
        LoaiDoiTuongKiemToan|string $targetType,
        string|int|null $targetId,
        string $description,
        ?array $oldData = null,
        ?array $newData = null,
        ?string $ip = null,
        ?string $userAgent = null
    ): NhatKyKiemToan {
        return $this->logger->log(
            actor: $actor,
            action: $action,
            targetType: $targetType,
            targetId: $targetId,
            description: $description,
            oldData: $oldData,
            newData: $newData,
            ip: $ip,
            userAgent: $userAgent
        );
    }
}

