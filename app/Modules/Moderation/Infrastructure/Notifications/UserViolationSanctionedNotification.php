<?php

namespace App\Modules\Moderation\Infrastructure\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

// Thông báo gửi tới tác giả khi bị chế tài xử lý vi phạm nội dung (trừ điểm uy tín, cảnh báo hoặc tạm khóa)
class UserViolationSanctionedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $targetTitle,
        private readonly int $penaltyPoints,
        private readonly int $newReputation,
        private readonly int $violationCount,
        private readonly string $warningLevel,
        private readonly bool $isLocked,
        private readonly string $reason
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        $title = $this->isLocked
            ? 'Tài khoản bị tạm khóa do vi phạm quy chế'
            : 'Cảnh báo vi phạm nội dung & Trừ điểm uy tín';

        $actionSummary = $this->isLocked
            ? "Tài khoản của bạn đã bị TẠM KHÓA do tái phạm quy chế học thuật ({$this->violationCount} lần vi phạm)."
            : "Nội dung của bạn đã vi phạm quy chế học thuật và bị trừ {$this->penaltyPoints} điểm uy tín (Điểm còn lại: {$this->newReputation}). Mức cảnh báo hiện tại: {$this->warningLevel}.";

        $message = "{$actionSummary} Đối tượng vi phạm: \"{$this->targetTitle}\". Căn cứ xử lý: {$this->reason}.";

        return new DatabaseMessage([
            'title'          => $title,
            'message'        => $message,
            'penalty_points' => $this->penaltyPoints,
            'new_reputation' => $this->newReputation,
            'so_lan_vi_pham' => $this->violationCount,
            'muc_canh_bao'   => $this->warningLevel,
            'is_locked'      => $this->isLocked,
            'reason'         => $this->reason,
            'url'            => route('profile.edit'),
        ]);
    }
}

