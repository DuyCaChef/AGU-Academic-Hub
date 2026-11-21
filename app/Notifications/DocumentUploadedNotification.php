<?php

namespace App\Notifications;

use App\Models\TaiLieu;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class DocumentUploadedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly TaiLieu $document,
        public readonly int $points = 10
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'title'       => 'Đăng tải tài liệu thành công',
            'message'     => "Tài liệu \"{$this->document->tieu_de}\" đã được ghi nhận. Bạn nhận được +{$this->points} điểm cống hiến tri thức.",
            'category'    => 'PERSONAL',
            'document_id' => $this->document->getKey(),
            'url'         => route('documents.show', $this->document->getKey()),
            'points'      => $this->points,
        ]);
    }
}
