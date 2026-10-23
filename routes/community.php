<?php

use App\Http\Controllers\CourseReviewController;
use App\Http\Controllers\KycController;
use Illuminate\Support\Facades\Route;

// Community Route (IAM, Reviews và Bounty)
// Phân hệ Cộng đồng sinh viên, Đánh giá môn học, Săn thưởng và Ví điểm

Route::middleware('auth')->group(function () {

    // =========================================================================
    // 1. XÁC THỰC DANH TÍNH & KYC (SINH VIÊN / CỰU SINH VIÊN)
    // =========================================================================
    Route::prefix('kyc')->name('kyc.')->group(function () {
        // Giao diện nhập hồ sơ KYC
        Route::get('/nop-ho-so', [KycController::class, 'create'])->name('submit');

        // Gửi ảnh thẻ SV / Bằng tốt nghiệp lên server private storage
        Route::post('/nop-ho-so', [KycController::class, 'store'])->name('store');

        // Xem trạng thái hồ sơ xác thực
        Route::get('/trang-thai', [KycController::class, 'status'])->name('status');

        // CẤP SIGNED URL XEM ẢNH: Stream ảnh từ private storage khi chữ ký hợp lệ
        Route::get('/xem-anh/{ma_xac_thuc}', [KycController::class, 'preview'])
            ->name('preview')
            ->middleware('signed');
    });

    // =========================================================================
    // 2. ĐÁNH GIÁ MÔN HỌC (COURSE REVIEWS AND RATING ENGINE)
    // =========================================================================
    // Xem danh sách review theo môn học (tham số ma_hoc_phan, ví dụ: COS305)
    Route::get('/mon-hoc/{ma_hoc_phan}/danh-gia', [CourseReviewController::class, 'index'])
        ->name('courses.reviews.index');

    // Gửi bài đánh giá môn học mới
    Route::post('/mon-hoc/{ma_hoc_phan}/danh-gia', [CourseReviewController::class, 'store'])
        ->name('courses.reviews.store');

    Route::prefix('danh-gia')->name('reviews.')->group(function () {
        // Chỉnh sửa review của chính mình
        Route::put('/{ma_danh_gia}', [CourseReviewController::class, 'update'])
            ->name('update');

        // Xóa mềm review
        Route::delete('/{ma_danh_gia}', [CourseReviewController::class, 'destroy'])
            ->name('destroy');

        // Đánh giá review hữu ích
        Route::post('/{ma_danh_gia}/huu-ich', [CourseReviewController::class, 'helpful'])
            ->name('helpful');

        // Báo cáo review vi phạm tiêu chuẩn cộng đồng
        Route::post('/{ma_danh_gia}/bao-cao', [CourseReviewController::class, 'report'])
            ->name('report');
    });

    // =========================================================================
    // 3. YÊU CẦU TÀI LIỆU VÀ SĂN THƯỞNG (WISHLIST VÀ BOUNTY)
    // =========================================================================
    Route::prefix('yeu-cau-tai-lieu')->name('document-requests.')->group(function () {
        // Danh sách các bài đăng tìm tài liệu
        Route::get('/', function () {
            return inertia('Wishlist/Index');
        })->name('index');

        // Tạo yêu cầu mới (đóng băng điểm thưởng từ vi_diem)
        Route::post('/', function () {
            return back()->with('info', 'Chức năng tạo yêu cầu đang được hoàn thiện.');
        })->name('store');

        // Chi tiết yêu cầu & các bài nộp phản hồi
        Route::get('/{ma_yeu_cau}', function ($ma_yeu_cau) {
            return inertia('Wishlist/Show', ['ma_yeu_cau' => $ma_yeu_cau]);
        })->name('show');

        // Đóng góp tài liệu phản hồi yêu cầu để nhận Bounty
        Route::post('/{ma_yeu_cau}/dong-gop', function ($ma_yeu_cau) {
            return back()->with('info', 'Chức năng đóng góp tài liệu đang được hoàn thiện.');
        })->name('fulfill');

        // Người tạo yêu cầu xác nhận tài liệu hợp lệ -> Giải ngân Bounty Points
        Route::post('/{ma_yeu_cau}/xac-nhan', function ($ma_yeu_cau) {
            return back()->with('success', 'Xác nhận thành công! Điểm thưởng đã được giải ngân.');
        })->name('confirm');

        // Hủy yêu cầu -> Hoàn lại điểm đóng băng vào ví
        Route::delete('/{ma_yeu_cau}', function ($ma_yeu_cau) {
            return back()->with('success', 'Đã hủy yêu cầu và hoàn điểm vào ví.');
        })->name('destroy');
    });

    // =========================================================================
    // 4. VÍ ĐIỂM CÁ NHÂN VÀ LỊCH SỬ BIẾN ĐỘNG (POINT WALLET)
    // =========================================================================
    Route::prefix('vi-diem')->name('wallet.')->group(function () {
        Route::get('/', function () {
            return inertia('Wallet/Index');
        })->name('index');
    });

    // =========================================================================
    // 5. QUẢN TRỊ KYC VÀ TÀI KHOẢN (KIỂM DUYỆT VIÊN VÀ QUẢN TRỊ VIÊN)
    // =========================================================================
    Route::prefix('quan-tri')->name('admin.')->group(function () {
        // Hàng đợi duyệt minh chứng thẻ SV / Bằng tốt nghiệp
        Route::get('/duyet-kyc', [KycController::class, 'adminQueue'])->name('kyc.index');

        // Phê duyệt hồ sơ KYC
        Route::put('/duyet-kyc/{ma_xac_thuc}/duyet', [KycController::class, 'approve'])->name('kyc.approve');

        // Từ chối hồ sơ KYC kèm lý do
        Route::put('/duyet-kyc/{ma_xac_thuc}/tu-choi', [KycController::class, 'reject'])->name('kyc.reject');

        // Quản lý danh sách người dùng & Cập nhật trạng thái (Khóa/Mở)
        Route::get('/nguoi-dung', function () {
            return inertia('Admin/Users/Index');
        })->name('users.index');

        Route::put('/nguoi-dung/{ma_nguoi_dung}/trang-thai', function ($ma_nguoi_dung) {
            return back()->with('success', 'Đã cập nhật trạng thái người dùng.');
        })->name('users.status');
    });

});