<?php

namespace App\Modules\Moderation\Infrastructure\Notifications;

use App\Models\BaoCaoViPham;
use App\Models\NguoiDung;
use App\Models\TaiLieu;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class NewViolationReportNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly BaoCaoViPham $report,
        private readonly TaiLieu $document,
        private readonly NguoiDung $reporter,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }


    // Gửi tới Admin và Moderator khi có báo cáo vi phạm mới 
    public function toDatabase(object $notifiable): DatabaseMessage
    {
        $documentTitle = $this->document->tieu_de;
        $reason = $this->report->ly_do;
        $reporterName = $this->reporter->ho_ten ?? 'Người dùng';

        return new DatabaseMessage([
            'title' => 'Có báo cáo vi phạm mới',
            'message' => "Tài liệu \"{$documentTitle}\" vừa nhận được một báo cáo vi phạm mới (Lý do: {$reason}) từ {$reporterName}.",
            'report_id' => $this->report->ma_bao_cao,
            'document_id' => $this->document->ma_tai_lieu,
            'document_title' => $documentTitle,
            'reporter_id' => $this->reporter->ma_nguoi_dung,
            'reason' => $reason,
            'url' => route('admin.reports.index'),
        ]);
    }
}