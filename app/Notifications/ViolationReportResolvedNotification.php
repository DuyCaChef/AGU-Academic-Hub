<?php

namespace App\Notifications;

use App\Models\BaoCaoViPham;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class ViolationReportResolvedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly BaoCaoViPham $report,
        private readonly string $actionLabel,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        $documentTitle = $this->report->taiLieu?->tieu_de ?? 'tài liệu';

        return new DatabaseMessage([
            'title' => 'Báo cáo vi phạm đã được xử lý',
            'message' => "Báo cáo liên quan đến \"{$documentTitle}\" đã được {$this->actionLabel}.",
            'report_id' => $this->report->ma_bao_cao,
            'document_id' => $this->report->ma_tai_lieu,
            'action' => $this->actionLabel,
        ]);
    }
}
