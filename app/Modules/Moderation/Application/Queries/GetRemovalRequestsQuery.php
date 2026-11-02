<?php

namespace App\Modules\Moderation\Application\Queries;

use App\Modules\Moderation\Infrastructure\Persistence\RemovalRequestRepository;

class GetRemovalRequestsQuery
{
    public function __construct(
        protected RemovalRequestRepository $repository
    ) {}

    // Lấy danh sách hàng đợi yêu cầu gỡ bỏ tài liệu
    public function execute(array $filters = []): array
    {
        $requests = $this->repository->paginateRemovalRequests($filters, 15);

        return [
            'requests'                    => $requests,
            'filters'                     => array_intersect_key($filters, array_flip(['status'])),
            'communityOwnershipThreshold' => config('documents.community_ownership_download_threshold', 10),
        ];
    }
}

