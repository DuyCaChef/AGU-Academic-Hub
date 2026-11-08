<?php

use App\Modules\Bounties\Presentation\Http\Controllers\AdminBountyPayoutController;
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

    // Hàng đợi duyệt giải ngân điểm Bounty - chỉ Admin và Kiểm duyệt viên
    Route::middleware('role:KIEM_DUYET_VIEN,QUAN_TRI_VIEN')
        ->prefix('quan-tri')
        ->name('admin.')
        ->group(function (): void {
            Route::prefix('yeu-cau-tai-lieu')->name('bounties.')->group(function (): void {
                Route::get('/cho-giai-ngan', [AdminBountyPayoutController::class, 'index'])->name('payout-queue');
                Route::get('/cho-giai-ngan/{ma_yeu_cau}', [AdminBountyPayoutController::class, 'show'])->name('payout-detail');
                Route::put('/cho-giai-ngan/{ma_yeu_cau}/duyet', [AdminBountyPayoutController::class, 'approve'])->name('approve-payout');
                Route::put('/cho-giai-ngan/{ma_yeu_cau}/tu-choi', [AdminBountyPayoutController::class, 'reject'])->name('reject-payout');
            });
        });
});
