<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class KycDecisionNotification extends Notification
{
    public function __construct(
        private readonly string $decision,
        private readonly string $message,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'decision' => $this->decision,
            'title' => $this->decision === 'DA_DUYET'
                ? 'Hồ sơ KYC đã được duyệt'
                : 'Hồ sơ KYC bị từ chối',
            'message' => $this->message,
        ];
    }
}
