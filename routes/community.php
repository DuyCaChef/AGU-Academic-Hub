<?php

use App\Http\Controllers\Bounties\AdminBountyPayoutController;
use App\Http\Controllers\Bounties\CorrectionRequestController;
use App\Http\Controllers\Bounties\WishlistBountyController;
use App\Http\Controllers\Notifications\NotificationController;
use App\Http\Controllers\Reviews\CourseReviewController;
use App\Http\Controllers\Wallet\WalletController;
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

        // Bình chọn cộng đồng Upvote / Downvote (Phase 3B2)
        Route::post('/{ma_danh_gia}/vote', [CourseReviewController::class, 'binhChonCongDong'])
            ->name('vote');

        // Đánh giá review hữu ích (Tương thích ngược)
        Route::post('/{ma_danh_gia}/huu-ich', [CourseReviewController::class, 'binhChonHuuIch'])
            ->name('helpful');

        // Báo cáo review vi phạm tiêu chuẩn cộng đồng
        Route::post('/{ma_danh_gia}/bao-cao', [CourseReviewController::class, 'baoCaoViPham'])
            ->name('report');
    });
});


// Tuyến đường quản lý yêu cầu tìm tài liệu (Wishlist), săn thưởng và ví điểm
Route::middleware('auth')->group(function (): void {
    // Phân hệ Yêu cầu tài liệu và Săn thưởng (Wishlist & Bounty)
    Route::prefix('yeu-cau-tai-lieu')->name('document-requests.')->group(function (): void {
        Route::get('/', [WishlistBountyController::class, 'index'])->name('index');
        Route::post('/', [WishlistBountyController::class, 'store'])->name('store');
        Route::get('/{ma_yeu_cau}', [WishlistBountyController::class, 'show'])->name('show');
        Route::post('/{ma_yeu_cau}/dong-gop', [WishlistBountyController::class, 'fulfill'])->name('fulfill');
        Route::post('/{ma_yeu_cau}/xac-nhan', [WishlistBountyController::class, 'confirm'])->name('confirm');
        Route::post('/{ma_yeu_cau}/tu-choi-dong-gop', [WishlistBountyController::class, 'rejectFulfillment'])->name('reject-fulfillment');
        Route::delete('/{ma_yeu_cau}', [WishlistBountyController::class, 'destroy'])->name('destroy');

        // Yêu cầu chỉnh sửa tài liệu sau khi hoàn thành Bounty
        Route::post('/{ma_yeu_cau}/yeu-cau-chinh-sua', [CorrectionRequestController::class, 'store'])->name('corrections.store');
    });

    // Luồng xử lý yêu cầu chỉnh sửa và các phiên bản thay thế
    Route::prefix('yeu-cau-chinh-sua')->name('corrections.')->group(function (): void {
        Route::post('/{ma_yeu_cau_chinh_sua}/phan-hoi', [CorrectionRequestController::class, 'respond'])->name('respond');
        Route::post('/{ma_yeu_cau_chinh_sua}/nop-thay-the', [CorrectionRequestController::class, 'submitReplacement'])->name('submit-replacement');
        Route::post('/{ma_yeu_cau_chinh_sua}/xac-nhan-hoan-thanh', [CorrectionRequestController::class, 'confirmCompletion'])->name('confirm-completion');
    });

    // Ví điểm cá nhân và lịch sử biến động
    Route::prefix('vi-diem')->name('wallet.')->group(function (): void {
        Route::get('/', [WalletController::class, 'index'])->name('index');
    });

    // Hàng đợi duyệt giải ngân điểm Bounty - chỉ Admin và Kiểm duyệt viên
    Route::middleware('role:KIEM_DUYET_VIEN,QUAN_TRI_VIEN')
        ->prefix('quan-tri')
        ->name('admin.')
        ->group(function (): void {
            Route::prefix('yeu-cau-tai-lieu')->name('bounties.')->group(function (): void {
                Route::get('/cho-giai-ngan', [AdminBountyPayoutController::class, 'index'])->name('payout-queue');
                Route::get('/cho-giai-ngan/{ma_yeu_cau}', [AdminBountyPayoutController::class, 'show'])->name('payout-detail');
                Route::put('/cho-giai-ngan/{ma_yeu_cau}/duyet', [AdminBountyPayoutController::class, 'approve'])->name('approve-payout');
                Route::put('/cho-giai-ngan/{ma_yeu_cau}/tu-choi', [AdminBountyPayoutController::class, 'reject'])->name('reject-payout');
            });
        });
});


Route::middleware(['auth'])->group(function () {
    // 1. Tuyến đường tiếng Việt (tương thích ngược hoàn toàn với frontend hiện có)
    Route::get('/thong-bao', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/thong-bao/so-luong-chua-doc', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::post('/thong-bao/{id}/doc', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/thong-bao/doc-tat-ca', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::delete('/thong-bao/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

    // 2. Tuyến đường chuẩn RESTful (hỗ trợ cả PATCH và DELETE)
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.center');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
});
