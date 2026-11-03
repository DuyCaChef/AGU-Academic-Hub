<?php

namespace App\Console\Commands;

use App\Modules\Documents\Application\Actions\CheckExpiringTrashDocumentsAction;
use Illuminate\Console\Command;

class NotifyExpiringTrashDocuments extends Command
{
    protected $signature = 'documents:notify-expiring-trash {--days=7 : Số ngày cảnh báo trước hạn}';

    protected $description = 'Quét và gửi thông báo cho Admin/Moderator về các tài liệu sắp hết hạn lưu trong thùng rác';

    public function handle(CheckExpiringTrashDocumentsAction $action): int
    {
        $days = (int) $this->option('days');
        $this->info("Đang kiểm tra tài liệu sắp hết hạn trong vòng {$days} ngày...");

        $result = $action->execute($days);

        if ($result['count'] === 0) {
            $this->info('Không có tài liệu nào sắp hết hạn trong thùng rác.');
            return self::SUCCESS;
        }

        $this->info("Đã phát hiện {$result['count']} tài liệu sắp hết hạn lưu trong thùng rác và gửi thông báo tới {$result['notified_count']} quản trị viên / kiểm duyệt viên.");

        return self::SUCCESS;
    }
}

