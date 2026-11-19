<?php

namespace App\Services\Auth\Actions;

use App\Models\NguoiDung;
use App\Services\Auth\AlumniKycRuleEvaluator;
use App\Services\Auth\Repositories\KycRepository;
use Throwable;

// Action phê duyệt hàng loạt hồ sơ KYC cựu sinh viên hợp lệ
class BulkApproveKycAction
{
    public function __construct(
        protected ApproveKycAction $approveAction,
        protected KycRepository $repository,
        protected AlumniKycRuleEvaluator $ruleEvaluator
    ) {}

    public function execute(array $ids, NguoiDung $reviewer): array
    {
        $approvedCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($ids as $id) {
            try {
                $kyc = $this->repository->findAlumniKycById((int) $id);
                if ($this->ruleEvaluator->canApprove($kyc)) {
                    $this->approveAction->execute((int) $id, $reviewer);
                    $approvedCount++;
                } else {
                    $failedCount++;
                }
            } catch (Throwable $e) {
                $failedCount++;
                $errors[] = "Hồ sơ #{$id}: {$e->getMessage()}";
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

