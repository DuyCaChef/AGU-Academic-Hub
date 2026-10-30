<?php

use App\Http\Controllers\CourseController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentModerationController;
use App\Http\Controllers\DocumentRemovalRequestController;
use App\Http\Controllers\DocumentReportController;
use App\Http\Controllers\FacultyController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ROUTES QUẢN LÝ KHOA, MÔN HỌC & TÀI LIỆU HỌC THUẬT (AGU ACADEMIC HUB)
|--------------------------------------------------------------------------
| Phụ trách: Hải (Dev A)
| Mô tả: Khai báo toàn bộ các endpoint tra cứu Khoa/Môn học, Upload tài liệu,
|        xem trước PDF, kiểm tra trùng lặp SHA-256, báo cáo & kiểm duyệt.
|--------------------------------------------------------------------------
*/

// =========================================================================
// 1. DANH MỤC KHOA & MÔN HỌC (PUBLIC / XEM CÔNG KHAI)
// =========================================================================

// --- Danh mục Khoa / Bộ môn ---
Route::prefix('khoa')->name('faculties.')->group(function () {
    // Lấy danh sách tất cả các Khoa/Bộ môn
    Route::get('/', [FacultyController::class, 'index'])->name('index');

    // Lấy danh sách môn học thuộc một Khoa cụ thể
    Route::get('/{id}/mon-hoc', [FacultyController::class, 'layDanhSachMonHoc'])->name('courses');
});

// --- Danh mục Môn học ---
Route::prefix('mon-hoc')->name('courses.')->group(function () {
    // Tìm kiếm / Tra cứu danh sách môn học
    Route::get('/', [CourseController::class, 'index'])->name('index');

    // Xem chi tiết môn học theo Mã học phần (VD: COS101, KTE201)
    Route::get('/{course_code}', [CourseController::class, 'show'])->name('show');

    // Lấy danh sách tài liệu thuộc môn học
    Route::get('/{course_code}/tai-lieu', [CourseController::class, 'documents'])->name('documents');
});


// =========================================================================
// 2. LUỒNG QUẢN LÝ TÀI LIỆU (YÊU CẦU ĐĂNG NHẬP & XÁC THỰC)
// =========================================================================
Route::middleware(['auth', 'verified'])->group(function () {

    Route::prefix('tai-lieu')->name('documents.')->group(function () {

        // --- Màn hình & Đăng tải tài liệu ---
        // Giao diện Form tải tài liệu lên
        Route::get('/dang-tai', [DocumentController::class, 'create'])->name('create');

        // Kiểm tra trước mã băm SHA-256 (Lọc trùng Lớp 1 - Check Deduplication API)
        Route::post('/kiem-tra-trung-lap', [DocumentController::class, 'checkDuplicate'])->name('check-duplicate');

        // Tải lên tài liệu mới (Đi qua Pipeline lọc trùng 2 lớp SHA-256 & SimHash)
        Route::post('/dang-tai', [DocumentController::class, 'store'])->name('store');

        // Tải lên phiên bản mới (v1.1, v2.0) cho tài liệu gốc đã có
        Route::post('/{id}/tao-phien-ban', [DocumentController::class, 'createVersion'])->name('create-version');

        // --- Trải nghiệm Đọc, Tải về & Tương tác ---
        // Xem thông tin chi tiết tài liệu
        Route::get('/{id}', [DocumentController::class, 'show'])->name('show');

        // Trình xem trước file PDF trực tuyến (Protected Stream Preview)
        Route::get('/{id}/xem-truoc', [DocumentController::class, 'preview'])->name('preview');

        // Tải file tài liệu về máy (Tự động tăng số lượt tải downloads_count)
        Route::get('/{id}/tai-ve', [DocumentController::class, 'download'])->name('download');

        // Thích / Bỏ thích tài liệu (Upvote)
        Route::post('/{id}/binh-chon', [DocumentController::class, 'upvote'])->name('upvote');

        // --- Chỉnh sửa & Quyền sở hữu (Policy Controlled) ---
        // Chỉnh sửa tiêu đề, mô tả hoặc danh mục tài liệu
        Route::put('/{id}', [DocumentController::class, 'update'])->name('update');

        // Xóa mềm tài liệu (Chỉ tác giả xóa được khi lượt tải = 0 và trong 24h đầu)
        Route::delete('/{id}', [DocumentController::class, 'destroy'])->name('destroy');

        // Gửi đơn xin gỡ tài liệu (Khi tài liệu đã thuộc Sở hữu cộng đồng >= 10 lượt tải)
        Route::post('/{id}/yeu-cau-go-bo', [DocumentController::class, 'requestRemoval'])->name('request-removal');
    });

    // --- Báo cáo vi phạm ---
    // Gửi báo cáo tài liệu (Sai nội dung, trùng lặp, chứa mã độc, vi phạm bản quyền)
    Route::post('/bao-cao/tai-lieu', [DocumentReportController::class, 'store'])->name('reports.document.store');
});


// =========================================================================
// 3. KHU VỰC QUẢN TRỊ & KIỂM DUYỆT (MODERATOR & ADMIN)
// =========================================================================
Route::middleware(['auth', 'role:KIEM_DUYET_VIEN,QUAN_TRI_VIEN'])->prefix('quan-tri')->name('admin.')->group(function () {

    // --- Quản lý Danh mục Môn học (Admin) ---
    Route::post('/mon-hoc', [CourseController::class, 'store'])->name('courses.store');
    Route::put('/mon-hoc/{id}', [CourseController::class, 'update'])->name('courses.update');
    Route::delete('/mon-hoc/{id}', [CourseController::class, 'destroy'])->name('courses.destroy');

    // --- Hàng đợi Báo cáo vi phạm (Report Queue) ---
    // Xem danh sách các báo cáo vi phạm cần xử lý
    Route::get('/bao-cao', [DocumentModerationController::class, 'indexReports'])->name('reports.index');
    Route::get('/bao-cao/{id}', [DocumentModerationController::class, 'showReport'])->name('reports.show');
    Route::get('/tai-lieu/cho-kiem-tra', [DocumentModerationController::class, 'reviewQueue'])->name('documents.review-queue');
    Route::get('/tai-lieu/{id}/kiem-tra', [DocumentModerationController::class, 'reviewDocument'])->name('documents.review-detail');
    Route::get('/tai-lieu/{id}/xem-tep', [DocumentModerationController::class, 'previewDocument'])->name('documents.preview');
    Route::put('/tai-lieu/{id}/quyet-dinh', [DocumentModerationController::class, 'decideDocument'])->name('documents.decide');

    // Quyết định xử lý báo cáo: Duyệt ẩn/xóa tài liệu hoặc Bác bỏ báo cáo
    Route::put('/bao-cao/{id}/giai-quyet', [DocumentModerationController::class, 'resolveReport'])->name('reports.resolve');
    Route::delete('/bao-cao/{id}', [DocumentModerationController::class, 'archiveReport'])->name('reports.archive');

    // --- Hàng đợi yêu cầu gỡ tài liệu thuộc quyền sở hữu cộng đồng ---
    Route::get('/yeu-cau-go-tai-lieu', [DocumentRemovalRequestController::class, 'index'])->name('documents.removal-requests.index');
    Route::get('/yeu-cau-go-tai-lieu/{id}', [DocumentRemovalRequestController::class, 'show'])->name('documents.removal-requests.show');
    Route::put('/yeu-cau-go-tai-lieu/{id}/quyet-dinh', [DocumentRemovalRequestController::class, 'decide'])->name('documents.removal-requests.decide');

    // --- Quản lý Thùng rác & Khôi phục (Soft Delete) ---
    // Xem danh sách tài liệu trong thùng rác
    Route::get('/tai-lieu/da-xoa', [DocumentModerationController::class, 'deletedDocuments'])->name('documents.deleted');

    // Khôi phục tài liệu đã xóa mềm
    Route::post('/tai-lieu/{id}/khoi-phuc', [DocumentModerationController::class, 'restoreDocument'])->name('documents.restore');
    Route::delete('/tai-lieu/{id}/xoa-vinh-vien', [DocumentModerationController::class, 'permanentlyDeleteDocument'])->name('documents.permanent-delete');
});