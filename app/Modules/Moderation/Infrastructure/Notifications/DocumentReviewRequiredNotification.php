<?php

namespace App\Modules\Moderation\Infrastructure\Notifications;

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
        $title = $this->autoHidden
            ? 'Tài liệu đã được tự động ẩn'
            : 'Tài liệu đạt ngưỡng tự động chuyển sang chờ kiểm tra';

        $message = $this->autoHidden
            ? "Tài liệu \"{$this->document->tieu_de}\" đạt {$this->validReports} báo cáo hợp lệ và đã được tự động ẩn để chờ kiểm tra."
            : "Tài liệu \"{$this->document->tieu_de}\" đạt {$this->validReports} báo cáo hợp lệ từ tài khoản uy tín và đã tự động chuyển sang trạng thái chờ kiểm tra.";

        return new DatabaseMessage([
            'title' => $title,
            'message' => $message,
            'document_id' => $this->document->ma_tai_lieu,
            'valid_reports' => $this->validReports,
            'auto_hidden' => $this->autoHidden,
            'url' => route('admin.documents.review-queue'),
        ]);
    }
}
