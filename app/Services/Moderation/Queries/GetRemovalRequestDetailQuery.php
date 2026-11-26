<?php

namespace App\Services\Moderation\Queries;

use App\Services\Moderation\Repositories\RemovalRequestRepository;

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

