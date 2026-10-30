<?php

namespace App\Notifications;

use App\Models\YeuCauGoBoTaiLieu;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class DocumentRemovalRequestResolvedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly YeuCauGoBoTaiLieu $request) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        $approved = $this->request->trang_thai === 'DA_DUYET';
        $title = $approved ? 'Yêu cầu gỡ tài liệu đã được duyệt' : 'Yêu cầu gỡ tài liệu bị từ chối';
        $message = $approved
            ? "Tài liệu \"{$this->request->taiLieu?->tieu_de}\" đã được gỡ khỏi kho tài liệu cộng đồng."
            : "Yêu cầu gỡ tài liệu \"{$this->request->taiLieu?->tieu_de}\" bị từ chối: {$this->request->ly_do_tu_choi}";

        return new DatabaseMessage([
            'title' => $title,
            'message' => $message,
            'removal_request_id' => $this->request->getKey(),
            'document_id' => $this->request->ma_tai_lieu,
            'status' => $this->request->trang_thai,
            'decision_reason' => $this->request->ly_do_tu_choi,
        ]);
    }
}
