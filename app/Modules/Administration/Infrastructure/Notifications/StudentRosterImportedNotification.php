<?php

namespace App\Modules\Administration\Infrastructure\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class StudentRosterImportedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly int $total,
        private readonly int $created,
        private readonly int $updated
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }


    // Gửi tới Admin khi có danh sách sinh viên mới được nạp
    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'title' => 'Có dữ liệu sinh viên mới được nạp',
            'message' => "Quá trình nạp danh sách sinh viên hoàn tất: Đã nạp {$this->total} bản ghi ({$this->created} thêm mới, {$this->updated} cập nhật).",
            'total' => $this->total,
            'created_count' => $this->created,
            'updated_count' => $this->updated,
            'url' => route('admin.raw-students.index'),
        ]);
    }
}
