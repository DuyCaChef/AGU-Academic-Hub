<?php

namespace App\Services\Bounties\Queries;

use App\Services\Bounties\Repositories\WishlistBountyRepository;

// Query trả về danh sách yêu cầu tài liệu đang chờ Admin/Mod duyệt giải ngân điểm Bounty
class GetBountyPayoutQueueQuery
{
    public function __construct(
        protected WishlistBountyRepository $bountyRepository
    ) {}

    public function execute(int $perPage = 15): array
    {
        $paginator = $this->bountyRepository->getPendingPayoutQueue($perPage);

        return [
            'queue' => $paginator,
        ];
    }
}

