<?php

namespace App\Modules\Bounties\Application\Queries;

use App\Models\NguoiDung;
use App\Modules\Bounties\Infrastructure\Persistence\WishlistBountyRepository;

// Query lấy chi tiết yêu cầu tìm tài liệu và các bài nộp phản hồi
class GetWishlistBountyDetailQuery
{
    public function __construct(
        protected WishlistBountyRepository $bountyRepository
    ) {}

    public function execute(int $maYeuCau, ?NguoiDung $currentUser): array
    {
        $yeuCau = $this->bountyRepository->findRequestById($maYeuCau);
        $isCreator = $currentUser && ($currentUser->ma_nguoi_dung === $yeuCau->ma_nguoi_tao);

        return [
            'item'       => $yeuCau,
            'is_creator' => (bool) $isCreator,
        ];
    }
}

