<?php

namespace App\Modules\Reviews\Application\Queries;

use App\Modules\Reviews\Infrastructure\Persistence\CourseReviewRepository;
use App\Modules\Reviews\Presentation\Http\Resources\CourseReviewResource;
use Illuminate\Http\Request;

// Query lấy dữ liệu môn học, thống kê 3 tiêu chí và danh sách đánh giá phân trang
class GetCourseReviewsQuery
{
    public function __construct(
        protected CourseReviewRepository $reviewRepository
    ) {}

    public function execute(Request $request, string $maHocPhan): array
    {
        // 1. Tìm môn học dựa theo mã học phần (thuộc khoa còn hiệu lực)
        $monHoc = $this->reviewRepository->findCourseByCode($maHocPhan);
        $courseId = $monHoc->ma_mon_hoc ?? $monHoc->getKey();

        // 2. Tính toán điểm trung bình 3 tiêu chí của môn học
        $thongKe = $this->reviewRepository->getCourseReviewStats($courseId);

        // 3. Truy vấn danh sách đánh giá phân trang
        $reviews = $this->reviewRepository->getPaginatedReviews($courseId, 10);
        $reviews->appends($request->query());

        // 4. Chuyển đổi qua Resource để che giấu mã người dùng ở tầng public
        $reviewsData = CourseReviewResource::collection($reviews);

        return [
            'mon_hoc_raw' => $monHoc,
            'mon_hoc' => [
                'ma_mon_hoc'  => $courseId,
                'ma_hoc_phan' => $monHoc->ma_hoc_phan,
                'ten_mon_hoc' => $monHoc->ten_mon_hoc,
                'so_tin_chi'  => $monHoc->so_tin_chi,
                'mo_ta'       => $monHoc->mo_ta,
            ],
            'thong_ke' => $thongKe,
            'reviews'  => $reviewsData,
        ];
    }
}

