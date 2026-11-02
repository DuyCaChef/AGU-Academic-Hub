<?php

namespace App\Modules\Reviews\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Reviews\Application\Actions\DeleteCourseReviewAction;
use App\Modules\Reviews\Application\Actions\ReportReviewViolationAction;
use App\Modules\Reviews\Application\Actions\StoreCourseReviewAction;
use App\Modules\Reviews\Application\Actions\ToggleHelpfulVoteAction;
use App\Modules\Reviews\Application\Actions\UpdateCourseReviewAction;
use App\Modules\Reviews\Application\Queries\GetCourseReviewsQuery;
use App\Modules\Reviews\Presentation\Http\Requests\ReportReviewRequest;
use App\Modules\Reviews\Presentation\Http\Requests\StoreCourseReviewRequest;
use App\Modules\Reviews\Presentation\Http\Requests\UpdateCourseReviewRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

// Controller điều phối luồng đánh giá môn học, xếp hạng và tương tác cộng đồng
class CourseReviewController extends Controller
{
    // Xem danh sách đánh giá của môn học (GET /mon-hoc/{ma_hoc_phan}/danh-gia)
    public function index(Request $request, string $ma_hoc_phan, GetCourseReviewsQuery $query): Response|JsonResponse
    {
        $data = $query->execute($request, $ma_hoc_phan);

        if ($request->wantsJson()) {
            return response()->json([
                'mon_hoc'  => $data['mon_hoc_raw'],
                'thong_ke' => $data['thong_ke'],
                'reviews'  => $data['reviews'],
            ]);
        }

        return Inertia::render('Reviews/CourseReviews', [
            'mon_hoc'  => $data['mon_hoc'],
            'thong_ke' => $data['thong_ke'],
            'reviews'  => $data['reviews'],
        ]);
    }

    // Gửi bài đánh giá môn học mới (POST /mon-hoc/{ma_hoc_phan}/danh-gia)
    public function store(StoreCourseReviewRequest $request, string $ma_hoc_phan, StoreCourseReviewAction $action): RedirectResponse
    {
        $action->execute($request->user(), $ma_hoc_phan, $request->all());

        return back()->with('success', 'Đánh giá môn học của bạn đã được đăng tải thành công.');
    }

    // Chỉnh sửa đánh giá của chính mình (PUT /danh-gia/{ma_danh_gia})
    public function update(UpdateCourseReviewRequest $request, int $ma_danh_gia, UpdateCourseReviewAction $action): RedirectResponse
    {
        $action->execute($request->user(), $ma_danh_gia, $request->all());

        return back()->with('success', 'Đã cập nhật bài đánh giá thành công.');
    }

    // Xóa mềm bài đánh giá (DELETE /danh-gia/{ma_danh_gia})
    public function destroy(Request $request, int $ma_danh_gia, DeleteCourseReviewAction $action): RedirectResponse
    {
        $action->execute($request->user(), $ma_danh_gia);

        return back()->with('success', 'Đã xóa bài đánh giá thành công.');
    }

    // Tương tác bình chọn hữu ích (POST /danh-gia/{ma_danh_gia}/huu-ich)
    public function binhChonHuuIch(Request $request, int $ma_danh_gia, ToggleHelpfulVoteAction $action): RedirectResponse
    {
        $result = $action->execute($request->user(), $ma_danh_gia);

        return back()->with($result['status'], $result['message']);
    }

    // Báo cáo đánh giá vi phạm chuẩn mực (POST /danh-gia/{ma_danh_gia}/bao-cao)
    public function baoCaoViPham(ReportReviewRequest $request, int $ma_danh_gia, ReportReviewViolationAction $action): RedirectResponse
    {
        $action->execute(
            $request->user(),
            $ma_danh_gia,
            $request->input('ly_do'),
            $request->input('chi_tiet')
        );

        return back()->with('success', 'Báo cáo vi phạm đã được gửi tới Ban Kiểm duyệt.');
    }
}

