<?php

namespace App\Modules\Moderation\Infrastructure\Notifications;

use App\Models\TaiLieu;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

// Thông báo gửi tới tác giả khi tài liệu mới đăng bị Ban kiểm duyệt từ chối
class DocumentRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly TaiLieu $document,
        private readonly string $reason
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'title'       => 'Tài liệu bị từ chối phê duyệt',
            'message'     => "Tài liệu \"{$this->document->tieu_de}\" của bạn không được phê duyệt. Lý do: {$this->reason}.",
            'document_id' => $this->document->ma_tai_lieu,
            'reason'      => $this->reason,
            'url'         => route('documents.my-documents'),
        ]);
    }
}

