<?php

namespace App\Modules\Bounties\Application\Queries;

use App\Modules\Bounties\Infrastructure\Persistence\WishlistBountyRepository;
use Illuminate\Http\Request;

// Query lấy danh sách yêu cầu tìm tài liệu kèm bộ lọc và danh mục môn học
class GetWishlistBountyListQuery
{
    public function __construct(
        protected WishlistBountyRepository $bountyRepository
    ) {}

    public function execute(Request $request): array
    {
        $filters = $request->only(['tab', 'q', 'trang_thai', 'ma_mon_hoc', 'phan_loai', 'sap_xep']);
        $userId = $request->user()?->ma_nguoi_dung;
        $danhSachYeuCau = $this->bountyRepository->getPaginatedWishlist($filters, 12, $userId);
        $danhSachYeuCau->appends($request->query());

        $courses = $this->bountyRepository->getActiveCourses();

        return [
            'requests' => $danhSachYeuCau,
            'filters'  => $filters,
            'courses'  => $courses,
        ];
    }
}

