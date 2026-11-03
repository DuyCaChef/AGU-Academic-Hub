<?php

namespace App\Modules\Identity\Infrastructure\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class AlumniKycDecisionNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $decision,
        private readonly string $message,
        private readonly ?string $reason = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        $isApproved = $this->decision === 'DA_DUYET';

        return new DatabaseMessage([
            'decision'  => $this->decision,
            'title'     => $isApproved
                ? 'Hồ sơ KYC đã được duyệt'
                : 'Hồ sơ KYC bị từ chối',
            'message'   => $this->message,
            'can_login' => $isApproved,
            'reason'    => $this->reason,
        ]);
    }
}
