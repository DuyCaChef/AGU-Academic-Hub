<?php

namespace App\Modules\Administration\Infrastructure\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class StudentRosterImportWarningNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly int $errorCount,
        private readonly int $duplicateCount,
        private readonly array $errors = [],
        private readonly array $duplicates = []
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    // Gửi tới Admin khi có lỗi hoặc bản ghi trùng trong quá trình nạp danh sách sinh viên
    public function toDatabase(object $notifiable): DatabaseMessage
    {
        $parts = [];
        if ($this->errorCount > 0) {
            $parts[] = "{$this->errorCount} bản ghi bị lỗi dữ liệu";
        }
        if ($this->duplicateCount > 0) {
            $parts[] = "{$this->duplicateCount} bản ghi trùng lặp";
        }
        $detailStr = implode(' và ', $parts);

        return new DatabaseMessage([
            'title' => 'Có lỗi hoặc bản ghi trùng trong quá trình import',
            'message' => "Quá trình nạp dữ liệu sinh viên phát hiện {$detailStr}. Vui lòng kiểm tra lại danh sách nạp.",
            'error_count' => $this->errorCount,
            'duplicate_count' => $this->duplicateCount,
            'errors' => array_slice($this->errors, 0, 10),
            'duplicates' => array_slice($this->duplicates, 0, 10),
            'url' => route('admin.raw-students.index'),
        ]);
    }
}

