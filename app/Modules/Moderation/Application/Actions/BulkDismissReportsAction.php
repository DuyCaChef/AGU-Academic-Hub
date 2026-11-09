<?php

namespace App\Modules\Moderation\Application\Actions;

use App\Models\NguoiDung;
use Throwable;

// Action bác bỏ hàng loạt báo cáo vi phạm không hợp lệ (hỗ trợ cả tài liệu và đánh giá)
class BulkDismissReportsAction
{
    public function __construct(
        protected ResolveViolationReportAction $resolveViolationAction,
        protected ResolveReviewReportAction $resolveReviewAction
    ) {}

    public function execute(array $reports, string $reason, NguoiDung $actor): array
    {
        $dismissedCount = 0;
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
                    $this->resolveViolationAction->execute($id, 'DISMISS', $reason, $actor);
                } else {
                    $this->resolveReviewAction->execute($id, 'DISMISS', $reason, $actor);
                }
                $dismissedCount++;
            } catch (Throwable $e) {
                $failedCount++;
                $errors[] = "Báo cáo #{$id} ({$type}): {$e->getMessage()}";
            }
        }

        return [
            'success'         => true,
            'dismissed_count' => $dismissedCount,
            'failed_count'    => $failedCount,
            'errors'          => $errors,
        ];
    }
}

