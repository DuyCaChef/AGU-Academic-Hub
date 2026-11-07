<?php

namespace App\Modules\AcademicCatalog\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AcademicCatalog\Application\Queries\GetPublicCoursesQuery;
use App\Modules\Reviews\Application\Queries\GetCourseReviewsQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    public function __construct(
        protected GetPublicCoursesQuery $coursesQuery
    ) {}

    // Danh sách môn học công khai
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'category', 'sort', 'ma_khoa', 'ma_chuyen_nganh']);
        $faculties = $this->coursesQuery->getPublicFaculties($request->user());
        $courses = $this->coursesQuery->getPaginatedCourses($filters);

        return Inertia::render('Courses/Index', [
            'faculties' => $faculties,
            'courses'   => $courses,
            'filters'   => $filters,
        ]);
    }

    // Chi tiết môn học theo Mã học phần (VD: COS101)
    public function show(Request $request, string $course_code): Response
    {
        $course = $this->coursesQuery->findActiveCourseByCode($course_code);
        $faculties = $this->coursesQuery->getPublicFaculties($request->user());

        $docFilters = array_merge(
            $request->only(['search', 'category', 'sort']),
            ['course_id' => $course->ma_mon_hoc]
        );
        $documents = $this->coursesQuery->getPaginatedDocuments($docFilters, 8);

        // Lấy thống kê và danh sách đánh giá của môn học (Phase 3B2)
        $reviewsQuery = app(GetCourseReviewsQuery::class);
        $reviewData = $reviewsQuery->execute($request, $course->ma_hoc_phan);

        // Lấy danh sách yêu cầu tài liệu thuộc môn học này (Phase 3B3)
        $wishlistRepo = app(\App\Modules\Bounties\Infrastructure\Persistence\WishlistBountyRepository::class);
        $wishlistFilters = [
            'ma_mon_hoc' => $course->ma_mon_hoc,
            'trang_thai' => $request->input('wishlist_status', 'TAT_CA'),
            'phan_loai'  => $request->input('wishlist_category'),
            'sap_xep'    => $request->input('wishlist_sort', 'moi_nhat'),
        ];
        $wishlistRequests = $wishlistRepo->getPaginatedWishlist($wishlistFilters, 8);
        $wishlistRequests->appends($request->query());

        return Inertia::render('Courses/Show', [
            'course'            => $course,
            'faculties'         => $faculties,
            'documents'         => $documents,
            'reviews'           => $reviewData['reviews'],
            'thong_ke'          => $reviewData['thong_ke'],
            'wishlist_requests' => $wishlistRequests,
            'filters'           => [
                'search'            => $request->input('search'),
                'category'          => $request->input('category'),
                'sort'              => $request->input('sort'),
                'tab'               => $request->input('tab', 'documents'),
                'wishlist_status'   => $request->input('wishlist_status', 'TAT_CA'),
                'wishlist_category' => $request->input('wishlist_category'),
                'wishlist_sort'     => $request->input('wishlist_sort', 'moi_nhat'),
            ],
        ]);
    }

    // Danh sách tài liệu thuộc môn học
    public function documents(Request $request, string $course_code): Response
    {
        $course = $this->coursesQuery->findActiveCourseByCode($course_code);
        $faculties = $this->coursesQuery->getPublicFaculties($request->user());

        $docFilters = array_merge(
            $request->only(['search', 'category', 'sort']),
            ['course_id' => $course->ma_mon_hoc]
        );
        $documents = $this->coursesQuery->getPaginatedDocuments($docFilters);

        return Inertia::render('Documents/Index', [
            'faculties' => $faculties,
            'course'    => $course,
            'documents' => $documents,
            'filters'   => $request->only(['search', 'category', 'sort']),
        ]);
    }
}
