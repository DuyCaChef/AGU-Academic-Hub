<?php

namespace App\Modules\Documents\Infrastructure\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class DocumentsExpiringInTrashNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly int $count,
        private readonly array $documentsData = []
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'title' => 'Có tài liệu sắp hết thời gian lưu trong thùng rác',
            'message' => "Hệ thống ghi nhận {$this->count} tài liệu sắp hết thời hạn lưu trữ trong thùng rác. Vui lòng rà soát trước khi tài liệu bị tự động xóa vĩnh viễn.",
            'count' => $this->count,
            'documents' => $this->documentsData,
            'url' => route('admin.documents.deleted'),
        ]);
    }
}