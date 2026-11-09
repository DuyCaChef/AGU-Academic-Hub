<?php

use App\Modules\Moderation\Presentation\Http\Controllers\DocumentModerationController;
use App\Modules\Moderation\Presentation\Http\Controllers\DocumentRemovalRequestController;
use App\Modules\Moderation\Presentation\Http\Controllers\DocumentReportController;
use App\Modules\Moderation\Presentation\Http\Controllers\ReviewModerationController;
use Illuminate\Support\Facades\Route;

// =========================================================================
// 1. NGƯỜI DÙNG BÁO CÁO VI PHẠM TÀI LIỆU (AUTH)
// =========================================================================
Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/bao-cao/tai-lieu', [DocumentReportController::class, 'store'])
        ->name('reports.document.store');
});

// =========================================================================
// 2. KHU VỰC QUẢN TRỊ & KIỂM DUYỆT (MODERATOR & ADMIN)
// =========================================================================
Route::middleware(['auth', 'role:KIEM_DUYET_VIEN,QUAN_TRI_VIEN'])
    ->prefix('quan-tri')
    ->name('admin.')
    ->group(function () {
        // --- Hàng đợi Báo cáo vi phạm tài liệu (Tự động chuyển hướng về Hàng đợi Báo cáo Thống nhất) ---
        Route::get('/bao-cao', [DocumentModerationController::class, 'indexReports'])->name('reports.index');

        // --- Xem & Giải quyết Báo cáo vi phạm tài liệu chi tiết (/quan-tri/bao-cao-tai-lieu/{id}) ---
        Route::get('/bao-cao-tai-lieu/{id}', [DocumentModerationController::class, 'showReport'])->name('reports.show');
        Route::put('/bao-cao-tai-lieu/{id}/giai-quyet', [DocumentModerationController::class, 'resolveReport'])->name('reports.resolve');
        Route::delete('/bao-cao-tai-lieu/{id}', [DocumentModerationController::class, 'archiveReport'])->name('reports.archive');

        // Chuyển hướng tương thích ngược từ URL cũ /quan-tri/bao-cao/{id} sang /quan-tri/bao-cao-tai-lieu/{id}
        Route::get('/bao-cao/{id}', fn($id) => redirect()->route('admin.reports.show', $id));

        // --- Hàng đợi Báo cáo đánh giá môn học (Course Review Report Queue) ---
        Route::get('/bao-cao-danh-gia', [ReviewModerationController::class, 'index'])->name('review-reports.index');
        Route::post('/bao-cao/bac-bo-hang-loat', [ReviewModerationController::class, 'bulkDismiss'])->name('reports.bulk-dismiss');
        Route::post('/bao-cao/luu-tru-hang-loat', [ReviewModerationController::class, 'bulkArchive'])->name('reports.bulk-archive');
        Route::get('/bao-cao-danh-gia/{id}', [ReviewModerationController::class, 'show'])->name('review-reports.show');
        Route::put('/bao-cao-danh-gia/{id}/giai-quyet', [ReviewModerationController::class, 'resolve'])->name('review-reports.resolve');
        Route::delete('/bao-cao-danh-gia/{id}', [ReviewModerationController::class, 'archive'])->name('review-reports.archive');

        // --- Hàng đợi Kiểm tra tài liệu nguy cơ cao & Phê duyệt tài liệu mới ---
        Route::get('/tai-lieu/cho-kiem-tra', [DocumentModerationController::class, 'reviewQueue'])->name('documents.review-queue');
        Route::post('/tai-lieu/duyet-hang-loat', [DocumentModerationController::class, 'bulkApproveNewDocuments'])->name('documents.bulk-approve');
        Route::get('/tai-lieu/{id}/kiem-tra', [DocumentModerationController::class, 'reviewDocument'])->name('documents.review-detail');
        Route::get('/tai-lieu/{id}/xem-tep', [DocumentModerationController::class, 'previewDocument'])->name('documents.preview');
        Route::put('/tai-lieu/{id}/quyet-dinh', [DocumentModerationController::class, 'decideDocument'])->name('documents.decide');
        Route::put('/tai-lieu/{id}/duyet', [DocumentModerationController::class, 'approveNewDocument'])->name('documents.approve');
        Route::put('/tai-lieu/{id}/tu-choi', [DocumentModerationController::class, 'rejectNewDocument'])->name('documents.reject');

        // --- Hàng đợi Yêu cầu gỡ bỏ tài liệu sở hữu cộng đồng ---
        Route::get('/yeu-cau-go-tai-lieu', [DocumentRemovalRequestController::class, 'index'])->name('documents.removal-requests.index');
        Route::get('/yeu-cau-go-tai-lieu/{id}', [DocumentRemovalRequestController::class, 'show'])->name('documents.removal-requests.show');
        Route::put('/yeu-cau-go-tai-lieu/{id}/quyet-dinh', [DocumentRemovalRequestController::class, 'decide'])->name('documents.removal-requests.decide');

        // --- Quản lý Thùng rác & Lịch sử xóa mềm (Xem danh sách) ---
        Route::get('/tai-lieu/da-xoa', [DocumentModerationController::class, 'deletedDocuments'])->name('documents.deleted');
    });

// =========================================================================
// 3. THAO TÁC NGUY CƠ CAO TRÊN THÙNG RÁC (CHỈ DÀNH CHO ADMIN)
// =========================================================================
Route::middleware(['auth', 'role:QUAN_TRI_VIEN'])
    ->prefix('quan-tri')
    ->name('admin.')
    ->group(function () {
        // Khôi phục tài liệu đã xóa mềm
        Route::post('/tai-lieu/{id}/khoi-phuc', [DocumentModerationController::class, 'restoreDocument'])->name('documents.restore');

        // Xóa vĩnh viễn tệp và bản ghi tài liệu khỏi hệ thống
        Route::delete('/tai-lieu/{id}/xoa-vinh-vien', [DocumentModerationController::class, 'permanentlyDeleteDocument'])->name('documents.permanent-delete');
    });
