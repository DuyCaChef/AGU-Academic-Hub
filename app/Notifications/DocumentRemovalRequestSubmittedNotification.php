<?php

namespace App\Notifications;

use App\Models\YeuCauGoBoTaiLieu;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class DocumentRemovalRequestSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly YeuCauGoBoTaiLieu $request) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'title' => 'Có yêu cầu gỡ tài liệu mới',
            'message' => "Tác giả yêu cầu gỡ tài liệu \"{$this->request->taiLieu?->tieu_de}\".",
            'removal_request_id' => $this->request->getKey(),
            'document_id' => $this->request->ma_tai_lieu,
        ]);
    }
}
