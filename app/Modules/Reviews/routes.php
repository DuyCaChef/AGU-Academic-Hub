<?php

use App\Modules\Reviews\Presentation\Http\Controllers\CourseReviewController;
use Illuminate\Support\Facades\Route;

// Tuyến đường quản lý đánh giá môn học và tương tác cộng đồng
Route::middleware('auth')->group(function (): void {
    // Xem danh sách review theo môn học
    Route::get('/mon-hoc/{ma_hoc_phan}/danh-gia', [CourseReviewController::class, 'index'])
        ->name('courses.reviews.index');

    // Gửi bài đánh giá môn học mới
    Route::post('/mon-hoc/{ma_hoc_phan}/danh-gia', [CourseReviewController::class, 'store'])
        ->name('courses.reviews.store');

    Route::prefix('danh-gia')->name('reviews.')->group(function (): void {
        // Chỉnh sửa review của chính mình
        Route::put('/{ma_danh_gia}', [CourseReviewController::class, 'update'])
            ->name('update');

        // Xóa mềm review
        Route::delete('/{ma_danh_gia}', [CourseReviewController::class, 'destroy'])
            ->name('destroy');

        // Đánh giá review hữu ích
        Route::post('/{ma_danh_gia}/huu-ich', [CourseReviewController::class, 'binhChonHuuIch'])
            ->name('helpful');

        // Báo cáo review vi phạm tiêu chuẩn cộng đồng
        Route::post('/{ma_danh_gia}/bao-cao', [CourseReviewController::class, 'baoCaoViPham'])
            ->name('report');
    });
});
