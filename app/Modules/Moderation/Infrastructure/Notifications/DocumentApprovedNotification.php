<?php

namespace App\Modules\Moderation\Infrastructure\Notifications;

use App\Models\TaiLieu;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

// Thông báo gửi tới tác giả khi tài liệu mới đăng được Ban kiểm duyệt phê duyệt công khai
class DocumentApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly TaiLieu $document,
        private readonly ?string $extraMessage = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        $message = $this->extraMessage ?? "Tài liệu \"{$this->document->tieu_de}\" của bạn đã được Ban kiểm duyệt phê duyệt và hiển thị công khai trên hệ thống.";

        return new DatabaseMessage([
            'title'       => 'Tài liệu đã được phê duyệt',
            'message'     => $message,
            'document_id' => $this->document->ma_tai_lieu,
            'url'         => route('documents.show', $this->document->ma_tai_lieu),
        ]);
    }
}

