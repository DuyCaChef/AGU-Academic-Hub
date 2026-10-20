<?php

use Illuminate\Support\Facades\Route;

// Community Route (IAM, Reviews và Bounty)
// Phân hệ Cộng  đồng sinh viên, Đánh giá môn học, Săn thưởng và Ví điểm

Route::middleware('auth')->group(function()
{
    // 1.Xác thực danh tính và KYC (Sinh viên/Cựu sinh viên)
    Route::prefix('kyc')->name('kyc.')->group(function(){
        // Giao diện nhập hồ sơ KYC
        Route::get('/nop-ho-so', function (){
            return inertia('Auth/KycSubmit');
        })->name('submit');

        // Gửi ảnh thẻ SV / Bằng tốt nghiệp lên server private storage
        Route::post('/nop-ho-so', function () {
            // Sẽ kết nối KycController@store ở Tuần 2
            return back()->with('info', 'Chức năng nộp minh chứng KYC đang được hoàn thiện.');
        })->name('store');

        // Xem trạng thái hồ sơ xác thực
        Route::get('/trang-thai',function(){
            return inertia('Auth/KycStatus');
        })->name('status');  
    });

    // Đánh giá môn học (Course reviews and Rating engine)
    // Xem danh sách review theo môn học (tham số ma_hoc_phan, ví dụ: COS305)
    Route::get('/mon-hoc/{ma_hoc_phan}/danh-gia', function($ma_hoc_phan){
        return inertia('Reviews/CourseReviews', ['ma_hoc_phan' => $ma_hoc_phan]);
    })->name('courses.reviews.index');

    // 2.Gửi bài đánh giá môn học mới
    Route::post('/mon-hoc/{ma_hoc_phan}/danh-gia', function ($ma_hoc_phan) {
        // Sẽ kết nối CourseReviewController@store ở Tuần 2 (áp dụng lọc từ cấm)
        return back()->with('info', 'Chức năng gửi đánh giá đang được hoàn thiện.');
    })->name('courses.reviews.store');

    Route::prefix('danh-gia')->name('reviews.')->group(function(){
        // Chỉnh sửa review của chính mình 
       Route::put('/{ma_danh_gia}', function ($ma_danh_gia) {
            return back()->with('info', 'Chức năng cập nhật đánh giá đang được hoàn thiện.');
        })->name('update');

        // Xóa mềm review
        Route::delete('/{ma_danh_gia}', function ($ma_danh_gia) {
            return back()->with('info', 'Chức năng xóa đánh giá đang được hoàn thiện.');
        })->name('destroy');

        // Đánh giá review hữu ích
        Route::post('/{ma_danh_gia}/huu-ich', function ($ma_danh_gia) {
            return back()->with('success', 'Đã ghi nhận phản hồi hữu ích.');
        })->name('helpful');

        // Báo cáo review vi phạm tiêu chuẩn cộng đồng
        Route::post('/{ma_danh_gia}/bao-cao', function ($ma_danh_gia) {
            return back()->with('success', 'Báo cáo vi phạm đã được gửi tới kiểm duyệt viên.');
        })->name('report');
    });

    // 3.Yêu cầu tài liệu và săn thưởng (Wishlist và Bounty)
    Route::prefix('yeu-cau-tai-lieu')->name('document-requests.')->group(function () {
        // Danh sách các bài đăng tìm tài liệu
        Route::get('/', function () {
            return inertia('Wishlist/Index');
        })->name('index');

        // Tạo yêu cầu mới (đóng băng điểm thưởng từ vi_diem)
        Route::post('/', function () {
            // Sẽ kết nối WishlistBountyController@store ở Tuần 2
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

    // 4.Ví điểm cá nhân và lịch sử biến động (Point wallet)
    Route::prefix('vi-diem')->name('wallet.')->group(function(){
        Route::get('/', function(){
            return inertia('Wallet/Index');
        })->name('index');
    });

    // 5.Quản trị KYC và tài khoản (Kiểm duyệt viên và quản trị viên)
    Route::prefix('quan-tri')->name('admin.')->group(function () {
        // Hàng đợi duyệt minh chứng thẻ SV
        Route::get('/duyet-kyc', function () {
            return inertia('Admin/Kyc/Index');
        })->name('kyc.index');

        Route::put('/duyet-kyc/{ma_xac_thuc}/duyet', function ($ma_xac_thuc) {
            return back()->with('success', 'Đã duyệt hồ sơ KYC thành công.');
        })->name('kyc.approve');

        Route::put('/duyet-kyc/{ma_xac_thuc}/tu-choi', function ($ma_xac_thuc) {
            return back()->with('warning', 'Đã từ chối hồ sơ KYC.');
        })->name('kyc.reject');

        // Quản lý danh sách người dùng & Cập nhật trạng thái (Khóa/Mở)
        Route::get('/nguoi-dung', function () {
            return inertia('Admin/Users/Index');
        })->name('users.index');

        Route::put('/nguoi-dung/{ma_nguoi_dung}/trang-thai', function ($ma_nguoi_dung) {
            return back()->with('success', 'Đã cập nhật trạng thái người dùng.');
        })->name('users.status');
    });
});