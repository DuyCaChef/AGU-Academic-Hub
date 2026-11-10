<?php

use App\Modules\Notifications\Presentation\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    // 1. Tuyến đường tiếng Việt (tương thích ngược hoàn toàn với frontend hiện có)
    Route::get('/thong-bao', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/thong-bao/so-luong-chua-doc', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::post('/thong-bao/{id}/doc', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/thong-bao/doc-tat-ca', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::delete('/thong-bao/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

    // 2. Tuyến đường chuẩn RESTful (hỗ trợ cả PATCH và DELETE)
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.center');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
});

