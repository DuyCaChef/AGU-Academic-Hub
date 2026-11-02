<?php

use App\Modules\Administration\Presentation\Http\Controllers\AdminDashboardController;
use App\Modules\Administration\Presentation\Http\Controllers\AdminStorageController;
use App\Modules\Administration\Presentation\Http\Controllers\AdminUserController;
use Illuminate\Support\Facades\Route;

// Tuyến đường Bảng điều khiển Quản trị (Dành cho Moderator và Admin)
Route::middleware(['auth', 'role:KIEM_DUYET_VIEN,QUAN_TRI_VIEN'])
    ->prefix('quan-tri')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/', AdminDashboardController::class)->name('dashboard');
    });

// Tuyến đường Quản trị Hệ thống (Dành riêng cho Quản trị viên - QUAN_TRI_VIEN)
Route::middleware(['auth', 'role:QUAN_TRI_VIEN'])
    ->prefix('quan-tri')
    ->name('admin.')
    ->group(function (): void {

        // Quản lý người dùng và phân quyền (RBAC)
        Route::prefix('nguoi-dung')->name('users.')->group(function (): void {
            Route::get('/', [AdminUserController::class, 'index'])->name('index');
            Route::get('/{ma_nguoi_dung}', [AdminUserController::class, 'show'])->name('show');
            Route::put('/{ma_nguoi_dung}/trang-thai', [AdminUserController::class, 'toggleStatus'])->name('status');
            Route::put('/{ma_nguoi_dung}/phan-quyen', [AdminUserController::class, 'updatePermissions'])->name('permissions');
            Route::post('/{ma_nguoi_dung}/vi-pham', [AdminUserController::class, 'recordViolation'])->name('violations');
        });

        // Chuyển hướng tương thích URL cũ /quan-tri/trang-thai về danh sách người dùng
        Route::get('/trang-thai', function () {
            return redirect()->route('admin.users.index');
        });

        // Theo dõi và quản lý dung lượng lưu trữ
        Route::prefix('luu-tru')->name('storage.')->group(function (): void {
            Route::get('/', [AdminStorageController::class, 'index'])->name('index');
            Route::post('/don-dep-thung-rac', [AdminStorageController::class, 'purgeExpiredTrash'])->name('purge-expired-trash');
        });
    });
