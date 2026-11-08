<?php

namespace App\Modules\Bounties\Application\Queries;

use App\Modules\Bounties\Infrastructure\Persistence\WishlistBountyRepository;

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
