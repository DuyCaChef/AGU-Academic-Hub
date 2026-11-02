<?php

namespace App\Modules\Moderation\Application\Queries;

use App\Modules\Moderation\Infrastructure\Persistence\RemovalRequestRepository;

class GetRemovalRequestDetailQuery
{
    public function __construct(
        protected RemovalRequestRepository $repository
    ) {}

    // Xem chi tiết một yêu cầu xin gỡ tài liệu
    public function execute(int $requestId): array
    {
        $removalRequest = $this->repository->findRemovalRequestById($requestId);

        return [
            'removalRequest'              => $removalRequest,
            'communityOwnershipThreshold' => config('documents.community_ownership_download_threshold', 10),
        ];
    }
}
