<?php

namespace App\Modules\Bounties\Application\Queries;

use App\Modules\Bounties\Infrastructure\Persistence\WishlistBountyRepository;

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
