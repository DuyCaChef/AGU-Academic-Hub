<?php

use App\Modules\Moderation\Presentation\Http\Controllers\DocumentModerationController;
use App\Modules\Moderation\Presentation\Http\Controllers\DocumentRemovalRequestController;
use App\Modules\Moderation\Presentation\Http\Controllers\DocumentReportController;
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
        // --- Hàng đợi Báo cáo vi phạm (Report Queue) ---
        Route::get('/bao-cao', [DocumentModerationController::class, 'indexReports'])->name('reports.index');
        Route::get('/bao-cao/{id}', [DocumentModerationController::class, 'showReport'])->name('reports.show');
        Route::put('/bao-cao/{id}/giai-quyet', [DocumentModerationController::class, 'resolveReport'])->name('reports.resolve');
        Route::delete('/bao-cao/{id}', [DocumentModerationController::class, 'archiveReport'])->name('reports.archive');

        // --- Hàng đợi Kiểm tra tài liệu nguy cơ cao ---
        Route::get('/tai-lieu/cho-kiem-tra', [DocumentModerationController::class, 'reviewQueue'])->name('documents.review-queue');
        Route::get('/tai-lieu/{id}/kiem-tra', [DocumentModerationController::class, 'reviewDocument'])->name('documents.review-detail');
        Route::get('/tai-lieu/{id}/xem-tep', [DocumentModerationController::class, 'previewDocument'])->name('documents.preview');
        Route::put('/tai-lieu/{id}/quyet-dinh', [DocumentModerationController::class, 'decideDocument'])->name('documents.decide');

        // --- Hàng đợi Yêu cầu gỡ bỏ tài liệu sở hữu cộng đồng ---
        Route::get('/yeu-cau-go-tai-lieu', [DocumentRemovalRequestController::class, 'index'])->name('documents.removal-requests.index');
        Route::get('/yeu-cau-go-tai-lieu/{id}', [DocumentRemovalRequestController::class, 'show'])->name('documents.removal-requests.show');
        Route::put('/yeu-cau-go-tai-lieu/{id}/quyet-dinh', [DocumentRemovalRequestController::class, 'decide'])->name('documents.removal-requests.decide');

        // --- Quản lý Thùng rác & Khôi phục (Soft Delete) ---
        Route::get('/tai-lieu/da-xoa', [DocumentModerationController::class, 'deletedDocuments'])->name('documents.deleted');
        Route::post('/tai-lieu/{id}/khoi-phuc', [DocumentModerationController::class, 'restoreDocument'])->name('documents.restore');
        Route::delete('/tai-lieu/{id}/xoa-vinh-vien', [DocumentModerationController::class, 'permanentlyDeleteDocument'])->name('documents.permanent-delete');
    });
