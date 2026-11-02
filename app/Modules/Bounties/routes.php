<?php

use App\Modules\Bounties\Presentation\Http\Controllers\WalletController;
use App\Modules\Bounties\Presentation\Http\Controllers\WishlistBountyController;
use Illuminate\Support\Facades\Route;

// Tuyến đường quản lý yêu cầu tìm tài liệu (Wishlist), săn thưởng và ví điểm
Route::middleware('auth')->group(function (): void {
    // Phân hệ Yêu cầu tài liệu và Săn thưởng (Wishlist & Bounty)
    Route::prefix('yeu-cau-tai-lieu')->name('document-requests.')->group(function (): void {
        Route::get('/', [WishlistBountyController::class, 'index'])->name('index');
        Route::post('/', [WishlistBountyController::class, 'store'])->name('store');
        Route::get('/{ma_yeu_cau}', [WishlistBountyController::class, 'show'])->name('show');
        Route::post('/{ma_yeu_cau}/dong-gop', [WishlistBountyController::class, 'fulfill'])->name('fulfill');
        Route::post('/{ma_yeu_cau}/xac-nhan', [WishlistBountyController::class, 'confirm'])->name('confirm');
        Route::delete('/{ma_yeu_cau}', [WishlistBountyController::class, 'destroy'])->name('destroy');
    });

    // Ví điểm cá nhân và lịch sử biến động
    Route::prefix('vi-diem')->name('wallet.')->group(function (): void {
        Route::get('/', [WalletController::class, 'index'])->name('index');
    });
});
