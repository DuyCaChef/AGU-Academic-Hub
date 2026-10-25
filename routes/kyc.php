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
Route::middleware(['auth'])->prefix('admin/kyc')->name('kyc.')->group(function () {

    // Danh sách hồ sơ chờ duyệt
    Route::get('/', [KycController::class, 'hangDoiKyc'])
        ->name('queue');

    // Xem trước ảnh minh chứng — route phải có middleware 'signed' để validate Signed URL
    Route::get('{id}/xem-truoc', [KycController::class, 'xemTruocMinhChung'])
        ->middleware('signed')
        ->name('preview');

    // Phê duyệt hồ sơ
    Route::post('{id}/duyet', [KycController::class, 'pheDuyetHoSo'])
        ->name('approve');

    // Từ chối hồ sơ (kèm lý do trong request body)
    Route::post('{id}/tu-choi', [KycController::class, 'tuChoiHoSo'])
        ->name('reject');
});
