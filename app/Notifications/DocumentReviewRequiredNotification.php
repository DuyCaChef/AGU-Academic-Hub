<?php

namespace App\Notifications;

use App\Models\TaiLieu;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class DocumentReviewRequiredNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly TaiLieu $document,
        private readonly int $validReports,
        private readonly bool $autoHidden,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'title' => $this->autoHidden ? 'Tài liệu đã được tự động ẩn' : 'Tài liệu cần kiểm tra',
            'message' => "Tài liệu \"{$this->document->tieu_de}\" có {$this->validReports} báo cáo hợp lệ từ tài khoản uy tín.",
            'document_id' => $this->document->ma_tai_lieu,
            'valid_reports' => $this->validReports,
            'auto_hidden' => $this->autoHidden,
        ]);
    }
}
