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
    Route::get('/alumni/register', [KycController::class, 'showRegistrationForm'])
        ->name('alumni.register');

    Route::post('/alumni/register', [KycController::class, 'storeAlumniRegistration'])
        ->name('alumni.register.store');
});

// ─── LUỒNG B: Admin/Mod quản lý hàng đợi KYC ─────────────────────────────────
Route::middleware(['auth'])->prefix('admin/kyc')->name('kyc.')->group(function () {

    // Danh sách hồ sơ chờ duyệt
    Route::get('/', [KycController::class, 'adminQueue'])
        ->name('queue');

    // Preview ảnh minh chứng — route phải có middleware 'signed' để validate Signed URL
    Route::get('{id}/preview', [KycController::class, 'preview'])
        ->middleware('signed')
        ->name('preview');

    // Phê duyệt hồ sơ
    Route::post('{id}/approve', [KycController::class, 'approve'])
        ->name('approve');

    // Từ chối hồ sơ (kèm lý do trong request body)
    Route::post('{id}/reject', [KycController::class, 'reject'])
        ->name('reject');
});
