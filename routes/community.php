<?php

use App\Http\Controllers\CourseReviewController;
use App\Http\Controllers\KycController;
use App\Http\Controllers\WishlistBountyController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AcademicCatalogController;

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

        // CấP SIGNED URL XEM ẢNH: Stream ảnh từ private storage khi chữ ký hợp lệ
        Route::get('/xem-anh/{ma_xac_thuc}', [KycController::class, 'xemTruocMinhChung'])
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
        Route::post('/{ma_danh_gia}/huu-ich', [CourseReviewController::class, 'binhChonHuuIch'])
            ->name('helpful');

        // Báo cáo review vi phạm tiêu chuẩn cộng đồng
        Route::post('/{ma_danh_gia}/bao-cao', [CourseReviewController::class, 'baoCaoViPham'])
            ->name('report');
    });

    // =========================================================================
    // 3. YÊU CẦU TÀI LIỆU VÀ SĂN THƯỞNG (WISHLIST VÀ BOUNTY)
    // =========================================================================
    Route::prefix('yeu-cau-tai-lieu')->name('document-requests.')->group(function () {
        // Danh sách các bài đăng tìm tài liệu
        Route::get('/', [WishlistBountyController::class, 'index'])->name('index');
        // Tạo yêu cầu mới (đóng băng điểm thưởng từ vi_diem)
        Route::post('/', [WishlistBountyController::class, 'store'])->name('store');

        // Chi tiết yêu cầu & các bài nộp phản hồi
       Route::get('/{ma_yeu_cau}', [WishlistBountyController::class, 'show'])->name('show');

       // Đóng góp tài liệu phản hồi yêu cầu để nhận Bounty
        Route::post('/{ma_yeu_cau}/dong-gop', [WishlistBountyController::class, 'fulfill'])->name('fulfill');

        // Người tạo yêu cầu xác nhận tài liệu hợp lệ -> Giải ngân Bounty Points
        Route::post('/{ma_yeu_cau}/xac-nhan', [WishlistBountyController::class, 'confirm'])->name('confirm');

        // Hủy yêu cầu -> Hoàn lại điểm đóng băng vào ví
        Route::delete('/{ma_yeu_cau}', [WishlistBountyController::class, 'destroy'])->name('destroy');
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
        Route::get('/duyet-kyc', [KycController::class, 'hangDoiKyc'])->name('kyc.index');

        // Phê duyệt hồ sơ KYC
        Route::put('/duyet-kyc/{ma_xac_thuc}/duyet', [KycController::class, 'pheDuyetHoSo'])->name('kyc.approve');

        // Từ chối hồ sơ KYC kèm lý do
        Route::put('/duyet-kyc/{ma_xac_thuc}/tu-choi', [KycController::class, 'tuChoiHoSo'])->name('kyc.reject');

        // Quản lý danh sách người dùng & Cập nhật trạng thái (Khóa/Mở)
        Route::get('/nguoi-dung', [AdminUserController::class, 'index'])
        ->name('users.index');

        Route::get('/nguoi-dung/{ma_nguoi_dung}', [AdminUserController::class, 'show'])
        ->name('users.show');

        Route::put('/nguoi-dung/{ma_nguoi_dung}/trang-thai', [AdminUserController::class, 'toggleStatus'])
        ->name('users.status');

        Route::put('/nguoi-dung/{ma_nguoi_dung}/phan-quyen', [AdminUserController::class, 'updatePermissions'])
        ->name('users.permissions');

        Route::post('/nguoi-dung/{ma_nguoi_dung}/vi-pham', [AdminUserController::class, 'recordViolation'])
        ->name('users.violations');

        // Chuyển hướng khi truy cập URL /quan-tri/trang-thai về trang Quản lý Người dùng
        Route::get('/trang-thai', function () {
            return redirect()->route('admin.users.index');
        });
    });

    Route::middleware('role:QUAN_TRI_VIEN')->prefix('quan-tri/danh-muc')->name('admin.catalog.')->group(function () {
        Route::get('/', [AcademicCatalogController::class, 'index'])->name('index');
        Route::post('/khoa', [AcademicCatalogController::class, 'storeFaculty'])->name('faculties.store');
        Route::put('/khoa/{maKhoa}', [AcademicCatalogController::class, 'updateFaculty'])->name('faculties.update');
        Route::put('/khoa/{maKhoa}/trang-thai', [AcademicCatalogController::class, 'toggleFaculty'])->name('faculties.toggle');
        Route::post('/chuyen-nganh', [AcademicCatalogController::class, 'storeMajor'])->name('majors.store');
        Route::put('/chuyen-nganh/{maChuyenNganh}', [AcademicCatalogController::class, 'updateMajor'])->name('majors.update');
        Route::put('/chuyen-nganh/{maChuyenNganh}/trang-thai', [AcademicCatalogController::class, 'toggleMajor'])->name('majors.toggle');
        Route::post('/mon-hoc', [AcademicCatalogController::class, 'storeCourse'])->name('courses.store');
        Route::put('/mon-hoc/{maMonHoc}', [AcademicCatalogController::class, 'updateCourse'])->name('courses.update');
        Route::put('/mon-hoc/{maMonHoc}/trang-thai', [AcademicCatalogController::class, 'toggleCourse'])->name('courses.toggle');
    });

});