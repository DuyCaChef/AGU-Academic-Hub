<?php

use App\Modules\Identity\Presentation\Http\Controllers\FirstTimePasswordController;
use App\Modules\Identity\Presentation\Http\Controllers\KycController;
use Illuminate\Support\Facades\Route;

// =========================================================================
// 1. ĐĂNG KÝ HỒ SƠ KYC CỰU SINH VIÊN (GUEST)
// =========================================================================
Route::middleware('guest')->group(function () {
    Route::get('/cuu-sinh-vien/dang-ky', [KycController::class, 'hienThiFormDangKy'])
        ->name('alumni.register');

    Route::post('/cuu-sinh-vien/dang-ky', [KycController::class, 'luuDangKyCuuSinhVien'])
        ->name('alumni.register.store');
});

// =========================================================================
// 2. XÁC THỰC MẬT KHẨU LẦN ĐẦU CHO SINH VIÊN (AUTH)
// =========================================================================
Route::middleware('auth')->group(function () {
    Route::get('/mat-khau-lan-dau', [FirstTimePasswordController::class, 'hienThiFormDoiMatKhau'])
        ->name('password.change');

    Route::post('/mat-khau-lan-dau', [FirstTimePasswordController::class, 'capNhatMatKhau'])
        ->name('password.change.update');
});

// =========================================================================
// 3. QUẢN TRỊ HÀNG ĐỢI DUYỆT KYC (ADMIN & MODERATOR)
// =========================================================================
Route::middleware('auth')->group(function () {
    // Alias điều hướng nhanh
    Route::prefix('quan-tri/kyc')->name('kyc.')->group(function () {
        Route::get('/', function () {
            return redirect()->route('admin.kyc.index');
        })->name('queue');
    });

    // Endpoint xem trước file minh chứng nhạy cảm (Private PII) qua Signed URL 30 phút
    Route::middleware('signed')
        ->get('/kyc/xem-anh/{ma_xac_thuc}', [KycController::class, 'xemTruocMinhChung'])
        ->name('kyc.preview');

    // Hàng đợi duyệt và thao tác phê duyệt / từ chối
    Route::prefix('quan-tri')->name('admin.')->group(function () {
        Route::get('/duyet-kyc', [KycController::class, 'hangDoiKyc'])->name('kyc.index');
        Route::put('/duyet-kyc/{ma_xac_thuc}/duyet', [KycController::class, 'pheDuyetHoSo'])->name('kyc.approve');
        Route::put('/duyet-kyc/{ma_xac_thuc}/tu-choi', [KycController::class, 'tuChoiHoSo'])->name('kyc.reject');
    });
});
