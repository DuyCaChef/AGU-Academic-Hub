<?php

namespace App\Services\Bounties\Queries;

use App\Services\Bounties\Repositories\WishlistBountyRepository;

// Query trả về chi tiết một yêu cầu tài liệu chờ duyệt giải ngân cho trang xem xét của Admin/Mod
class GetBountyPayoutDetailQuery
{
    public function __construct(
        protected WishlistBountyRepository $bountyRepository
    ) {}

    public function execute(int $maYeuCau): array
    {
        $yeuCau = $this->bountyRepository->getPendingPayoutDetail($maYeuCau);

        return [
            'yeuCau' => $yeuCau,
        ];
    }
}

