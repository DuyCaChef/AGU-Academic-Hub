<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;

use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/trang-chu', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/ho-so', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/ho-so', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/ho-so', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Route xem thử GuestLayout
Route::get('/test-khach-layout', function () {
    return inertia()->render('TestGuest');
});

// Route xem thử AdminLayout
Route::get('/test-admin-layout', function () {
    return inertia()->render('TestAdmin');
});

require __DIR__ . '/auth.php';
require __DIR__ . '/kyc.php';

