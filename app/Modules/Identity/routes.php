<?php

use App\Modules\Identity\Presentation\Http\Controllers\AuthenticatedSessionController;
use App\Modules\Identity\Presentation\Http\Controllers\ConfirmablePasswordController;
use App\Modules\Identity\Presentation\Http\Controllers\EmailVerificationController;
use App\Modules\Identity\Presentation\Http\Controllers\FirstTimePasswordController;
use App\Modules\Identity\Presentation\Http\Controllers\KycController;
use App\Modules\Identity\Presentation\Http\Controllers\NewPasswordController;
use App\Modules\Identity\Presentation\Http\Controllers\PasswordController;
use App\Modules\Identity\Presentation\Http\Controllers\PasswordResetLinkController;
use App\Modules\Identity\Presentation\Http\Controllers\ProfileController;
use App\Modules\Identity\Presentation\Http\Controllers\RegisteredUserController;
use Illuminate\Support\Facades\Route;

// =========================================================================
// 1. TUYẾN ĐƯỜNG DÀNH CHO KHÁCH CHƯA ĐĂNG NHẬP (GUEST)
// =========================================================================
Route::middleware('guest')->group(function (): void {
    // Đăng ký hồ sơ KYC Cựu sinh viên
    Route::get('/cuu-sinh-vien/dang-ky', [KycController::class, 'hienThiFormDangKy'])
        ->name('alumni.register');
    Route::post('/cuu-sinh-vien/dang-ky', [KycController::class, 'luuDangKyCuuSinhVien'])
        ->name('alumni.register.store');

    // Đăng ký tài khoản sinh viên
    Route::get('/dang-ky', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/dang-ky', [RegisteredUserController::class, 'store']);
    Route::get('/register', [RegisteredUserController::class, 'create']);
    Route::post('/register', [RegisteredUserController::class, 'store']);

    // Đăng nhập hệ thống (hỗ trợ cả URI tiếng Việt và alias tiếng Anh)
    Route::get('/dang-nhap', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/dang-nhap', [AuthenticatedSessionController::class, 'store']);
    Route::get('/login', [AuthenticatedSessionController::class, 'create']);
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);

    // Quên và đặt lại mật khẩu
    Route::get('/quen-mat-khau', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');
    Route::post('/quen-mat-khau', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create']);
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store']);

    Route::get('/dat-lai-mat-khau/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');
    Route::post('/dat-lai-mat-khau', [NewPasswordController::class, 'store'])
        ->name('password.store');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create']);
    Route::post('/reset-password', [NewPasswordController::class, 'store']);
});

// =========================================================================
// 2. TUYẾN ĐƯỜNG DÀNH CHO NGƯỜI DÙNG ĐÃ ĐĂNG NHẬP (AUTH)
// =========================================================================
Route::middleware('auth')->group(function (): void {
    // Đổi mật khẩu lần đầu cho sinh viên mới kích hoạt
    Route::get('/mat-khau-lan-dau', [FirstTimePasswordController::class, 'hienThiFormDoiMatKhau'])
        ->name('password.change');
    Route::post('/mat-khau-lan-dau', [FirstTimePasswordController::class, 'capNhatMatKhau'])
        ->name('password.change.update');

    // Quản lý hồ sơ cá nhân
    Route::get('/ho-so', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/ho-so', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/ho-so', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/profile', [ProfileController::class, 'edit']);
    Route::patch('/profile', [ProfileController::class, 'update']);
    Route::delete('/profile', [ProfileController::class, 'destroy']);

    // Xác minh email
    Route::get('/xac-minh-email', [EmailVerificationController::class, 'prompt'])
        ->name('verification.notice');
    Route::get('/xac-minh-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/thong-bao-xac-minh', [EmailVerificationController::class, 'notification'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
    Route::get('/verify-email', [EmailVerificationController::class, 'prompt']);
    Route::get('/verify-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1']);
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'notification'])
        ->middleware('throttle:6,1');

    // Xác nhận mật khẩu và đổi mật khẩu
    Route::get('/xac-nhan-mat-khau', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');
    Route::post('/xac-nhan-mat-khau', [ConfirmablePasswordController::class, 'store']);
    Route::get('/confirm-password', [ConfirmablePasswordController::class, 'show']);
    Route::post('/confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('/mat-khau', [PasswordController::class, 'update'])->name('password.update');
    Route::put('/password', [PasswordController::class, 'update']);

    // Đăng xuất
    Route::post('/dang-xuat', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);
});

// =========================================================================
// 3. QUẢN TRỊ HÀNG ĐỢI DUYỆT KYC (ADMIN & MODERATOR)
// =========================================================================
Route::middleware(['auth', 'role:KIEM_DUYET_VIEN,QUAN_TRI_VIEN'])->group(function (): void {
    // Alias điều hướng nhanh
    Route::prefix('quan-tri/kyc')->name('kyc.')->group(function (): void {
        Route::get('/', function () {
            return redirect()->route('admin.kyc.index');
        })->name('queue');
    });

    // Hàng đợi duyệt và thao tác phê duyệt / từ chối hồ sơ cựu sinh viên
    Route::prefix('quan-tri')->name('admin.')->group(function (): void {
        Route::get('/duyet-kyc', [KycController::class, 'hangDoiKyc'])->name('kyc.index');
        Route::put('/duyet-kyc/{ma_xac_thuc}/duyet', [KycController::class, 'pheDuyetHoSo'])->name('kyc.approve');
        Route::put('/duyet-kyc/{ma_xac_thuc}/tu-choi', [KycController::class, 'tuChoiHoSo'])->name('kyc.reject');
    });
});

// Endpoint xem trước file minh chứng nhạy cảm (Private PII) qua Signed URL 30 phút
Route::middleware(['auth', 'signed'])
    ->get('/kyc/xem-anh/{ma_xac_thuc}', [KycController::class, 'xemTruocMinhChung'])
    ->name('kyc.preview');
