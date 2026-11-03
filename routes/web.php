<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Trang chủ chào đón người dùng
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin'       => Route::has('login'),
        'canRegister'    => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion'     => PHP_VERSION,
    ]);
})->name('home');

// Bảng điều khiển người dùng sau khi đăng nhập
Route::get('/trang-chu', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Tuyến đường thông báo hệ thống (Notification API)
Route::middleware('auth')->group(function () {
    Route::get('/thong-bao', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/thong-bao/{id}/doc', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/thong-bao/doc-tat-ca', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
});

// Tuyến đường kiểm thử giao diện
Route::get('/test-khach-layout', function () {
    return inertia()->render('TestGuest');
});

Route::get('/test-admin-layout', function () {
    return inertia()->render('TestAdmin');
});
