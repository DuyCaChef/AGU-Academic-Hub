<?php

namespace App\Modules\Moderation\Application\Actions;

use App\Models\NguoiDung;
use Throwable;

// Action phê duyệt hàng loạt tài liệu mới đăng ở trạng thái chờ duyệt (CHO_DUYET)
class BulkApproveNewDocumentsAction
{
    public function __construct(
        protected ApproveNewDocumentAction $approveAction
    ) {}

    public function execute(array $documentIds, NguoiDung $actor): array
    {
        $approvedCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($documentIds as $id) {
            $id = (int) $id;
            if ($id <= 0) {
                continue;
            }

            try {
                $result = $this->approveAction->execute($id, $actor);
                if (!empty($result['success'])) {
                    $approvedCount++;
                } else {
                    $failedCount++;
                    $errors[] = "Tài liệu #{$id}: " . ($result['message'] ?? 'Không đủ điều kiện duyệt');
                }
            } catch (Throwable $e) {
                $failedCount++;
                $errors[] = "Tài liệu #{$id}: {$e->getMessage()}";
            }
        }

        return [
            'success'        => true,
            'approved_count' => $approvedCount,
            'failed_count'   => $failedCount,
            'errors'         => $errors,
        ];
    }
}

