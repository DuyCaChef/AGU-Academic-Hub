<?php

namespace App\Modules\Identity\Infrastructure\Notifications;

use App\Models\YeuCauXacThuc;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class AlumniKycReceivedNotification extends Notification
{
    use Queueable;

    // Hàm khởi tạo
    public function __construct(
        private readonly YeuCauXacThuc $kyc
    ) {}

    // Phương thức xác định các kênh thông báo sẽ sử dụng
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    // Phương thức xử lý dữ liệu thông báo
    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'title' => 'Đã tiếp nhận hồ sơ KYC',
            'message' => 'Hồ sơ xác thực cựu sinh viên của bạn đã được tiếp nhận thành công. Hồ sơ đang chờ xử lý bởi Ban quản trị.',
            'kyc_id' => $this->kyc->ma_yeu_cau_xac_thuc,
            'mssv' => $this->kyc->mssv,
            'status' => 'CHO_DUYET',
        ]);
    }
}

