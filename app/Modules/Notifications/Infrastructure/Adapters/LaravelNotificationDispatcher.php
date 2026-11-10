<?php

namespace App\Modules\Notifications\Infrastructure\Adapters;

use App\Models\NguoiDung;
use App\Models\ThongBao;
use App\Modules\Notifications\Application\Contracts\NotificationDispatcherInterface;
use App\Modules\Notifications\Application\DTOs\NotificationPayload;
use App\Modules\Notifications\Infrastructure\Notifications\SystemNotification;
use Illuminate\Support\Facades\Log;
use Throwable;

class LaravelNotificationDispatcher implements NotificationDispatcherInterface
{
    // Gửi thông báo tới một người nhận duy nhất
    public function dispatch(NguoiDung $recipient, NotificationPayload $payload): bool
    {
        // 1. Kiểm tra tính hợp lệ của người nhận
        if (!$recipient->exists || $recipient->trashed()) {
            Log::warning("[NotificationDispatcher] Bỏ qua người nhận không hợp lệ hoặc đã bị xóa mềm: ID {$recipient->ma_nguoi_dung}");
            return false;
        }

        // 2. Chống gửi trùng lặp (Idempotency Check)
        if (!empty($payload->idempotencyKey)) {
            $isDuplicate = ThongBao::query()
                ->where('notifiable_type', $recipient->getMorphClass())
                ->where('notifiable_id', $recipient->getKey())
                ->where('du_lieu', 'like', '%"idempotency_key":"' . $payload->idempotencyKey . '"%')
                ->exists();

            if ($isDuplicate) {
                Log::info("[NotificationDispatcher] Đã bỏ qua gửi trùng thông báo (Idempotency Key: {$payload->idempotencyKey}) cho người dùng: {$recipient->ma_nguoi_dung}");
                return true;
            }
        }

        // 3. Thực hiện gửi thông báo qua Laravel Notification
        try {
            $recipient->notify(new SystemNotification($payload));
            return true;
        } catch (Throwable $e) {
            Log::error("[NotificationDispatcher] Lỗi khi gửi thông báo tới người dùng {$recipient->ma_nguoi_dung}: " . $e->getMessage(), [
                'exception' => $e,
                'payload'   => $payload->toArray(),
            ]);
            return false;
        }
    }

    // Gửi thông báo tới danh sách nhiều người nhận
    public function dispatchMultiple(iterable $recipients, NotificationPayload $payload): int
    {
        $successCount = 0;

        foreach ($recipients as $recipient) {
            if ($recipient instanceof NguoiDung && $this->dispatch($recipient, $payload)) {
                $successCount++;
            }
        }

        return $successCount;
    }
}

