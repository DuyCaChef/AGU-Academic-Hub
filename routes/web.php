<?php

use App\Http\Controllers\DashboardController;
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
});

// Bảng điều khiển người dùng sau khi đăng nhập
Route::get('/trang-chu', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Tuyến đường kiểm thử giao diện
Route::get('/test-khach-layout', function () {
    return inertia()->render('TestGuest');
});

Route::get('/test-admin-layout', function () {
    return inertia()->render('TestAdmin');
});
