<?php

use App\Modules\Documents\Presentation\Http\Controllers\DocumentController;
use Illuminate\Support\Facades\Route;

// =========================================================================
// LUỒNG QUẢN LÝ TÀI LIỆU (YÊU CẦU ĐĂNG NHẬP & XÁC THỰC)
// =========================================================================
Route::middleware(['auth', 'verified'])->prefix('tai-lieu')->name('documents.')->group(function () {
    // Danh sách tài liệu công khai
    Route::get('/', [DocumentController::class, 'index'])->name('index');

    // Giao diện form tải tài liệu lên
    Route::get('/dang-tai', [DocumentController::class, 'create'])->name('create');

    // Kiểm tra trước mã băm SHA-256 (Lọc trùng Lớp 1)
    Route::post('/kiem-tra-trung-lap', [DocumentController::class, 'checkDuplicate'])->name('check-duplicate');

    // Tải lên tài liệu mới
    Route::post('/dang-tai', [DocumentController::class, 'store'])->name('store');

    // Tải lên phiên bản mới cho tài liệu đã có
    Route::post('/{id}/tao-phien-ban', [DocumentController::class, 'createVersion'])->name('create-version');

    // Danh sách tài liệu của người dùng hiện tại (cho picker)
    Route::get('/cua-toi', [DocumentController::class, 'myDocuments'])->name('my-documents');

    // Xem thông tin chi tiết tài liệu
    Route::get('/{id}', [DocumentController::class, 'show'])->name('show');

    // Xem trước trực tuyến tài liệu PDF
    Route::get('/{id}/xem-truoc', [DocumentController::class, 'preview'])->name('preview');

    // Tải file tài liệu về máy (Tự động tăng lượt tải)
    Route::get('/{id}/tai-ve', [DocumentController::class, 'download'])->name('download');

    // Bình chọn / Thích tài liệu (Upvote)
    Route::post('/{id}/binh-chon', [DocumentController::class, 'upvote'])->name('upvote');

    // Chỉnh sửa tiêu đề, mô tả hoặc phân loại tài liệu
    Route::put('/{id}', [DocumentController::class, 'update'])->name('update');

    // Xóa mềm tài liệu (Chỉ tác giả xóa được khi lượt tải = 0 và trong 24h đầu)
    Route::delete('/{id}', [DocumentController::class, 'destroy'])->name('destroy');

    // Gửi đơn xin gỡ tài liệu (Khi tài liệu đã thuộc Sở hữu cộng đồng >= 10 lượt tải)
    Route::post('/{id}/yeu-cau-go-bo', [DocumentController::class, 'requestRemoval'])->name('request-removal');
});
