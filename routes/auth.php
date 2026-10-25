<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    // Sinh viên không tự đăng ký. Tài khoản được cấp từ dữ liệu thô nhà trường.
    // Đối với cựu sinh viên, đăng ký nộp hồ sơ KYC qua route 'alumni.register' trong routes/kyc.php
    // Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    // Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('dang-nhap', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('dang-nhap', [AuthenticatedSessionController::class, 'store']);

    Route::get('quen-mat-khau', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('quen-mat-khau', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('dat-lai-mat-khau/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('dat-lai-mat-khau', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
    // Đổi mật khẩu bắt buộc cho sinh viên lần đầu đăng nhập
    Route::get('mat-khau-lan-dau', [\App\Http\Controllers\Auth\FirstTimePasswordController::class, 'hienThiFormDoiMatKhau'])
        ->name('password.change');

    Route::post('mat-khau-lan-dau', [\App\Http\Controllers\Auth\FirstTimePasswordController::class, 'capNhatMatKhau'])
        ->name('password.change.update');

    Route::get('xac-minh-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('xac-minh-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/thong-bao-xac-minh', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('xac-nhan-mat-khau', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('xac-nhan-mat-khau', [ConfirmablePasswordController::class, 'store']);

    Route::put('mat-khau', [PasswordController::class, 'update'])->name('password.update');

    Route::post('dang-xuat', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
