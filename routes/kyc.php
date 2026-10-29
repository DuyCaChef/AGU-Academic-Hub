<?php

use App\Http\Controllers\KycController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| KYC Routes — Định danh & Xác thực tài khoản
|--------------------------------------------------------------------------
|
| LUỒNG A — Cựu sinh viên (Alumni KYC):
|   Guest nộp hồ sơ → Admin duyệt → Tài khoản được kích hoạt.
|
| LUỒNG B — Admin/Mod quản lý hàng đợi KYC:
|   Xem danh sách, xem ảnh minh chứng (Signed URL), approve/reject.
|
*/

// ─── LUỒNG A: Form đăng ký cựu sinh viên (public — không cần đăng nhập) ──────
Route::middleware('guest')->group(function () {
    Route::get('/cuu-sinh-vien/dang-ky', [KycController::class, 'hienThiFormDangKy'])
        ->name('alumni.register');

    Route::post('/cuu-sinh-vien/dang-ky', [KycController::class, 'luuDangKyCuuSinhVien'])
        ->name('alumni.register.store');
});

// ─── LUỒNG B: Admin/Mod quản lý hàng đợi KYC ─────────────────────────────────
// Toàn bộ route Quản trị KYC đã được quy hoạch tập trung tại routes/community.php
// (Prefix: quan-tri, Name: admin.kyc.* và route Signed URL xem ảnh kyc.preview)
Route::middleware(['auth'])->prefix('admin/kyc')->name('kyc.')->group(function () {
    // Redirect về route chuẩn hóa của Admin
    Route::get('/', function () {
        return redirect()->route('admin.kyc.index');
    })->name('queue');
});

Route::middleware(['auth', 'signed'])->get('/kyc/xem-anh/{ma_xac_thuc}', [KycController::class, 'xemTruocMinhChung'])
    ->name('kyc.preview');
