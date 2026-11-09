<?php

namespace App\Modules\Moderation\Application\Actions;

use Throwable;

// Action lưu trữ hàng loạt bản ghi báo cáo đã xử lý hoặc bác bỏ để dọn sạch hàng đợi
class BulkArchiveReportsAction
{
    public function __construct(
        protected ArchiveViolationReportAction $archiveViolationAction,
        protected ArchiveReviewReportAction $archiveReviewAction
    ) {}

    public function execute(array $reports): array
    {
        $archivedCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($reports as $item) {
            $id = (int) ($item['id'] ?? 0);
            $type = strtoupper((string) ($item['type'] ?? 'DANH_GIA'));

            if ($id <= 0) {
                continue;
            }

            try {
                if ($type === 'TAI_LIEU') {
                    $this->archiveViolationAction->execute($id);
                } else {
                    $this->archiveReviewAction->execute($id);
                }
                $archivedCount++;
            } catch (Throwable $e) {
                $failedCount++;
                $errors[] = "Báo cáo #{$id} ({$type}): {$e->getMessage()}";
            }
        }

        return [
            'success'        => true,
            'archived_count' => $archivedCount,
            'failed_count'   => $failedCount,
            'errors'         => $errors,
        ];
    }
}

