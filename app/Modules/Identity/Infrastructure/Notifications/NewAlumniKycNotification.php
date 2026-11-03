<?php

namespace App\Modules\Identity\Infrastructure\Notifications;

use App\Models\NguoiDung;
use App\Models\YeuCauXacThuc;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class NewAlumniKycNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly YeuCauXacThuc $kyc,
        private readonly NguoiDung $alumni,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }


    // Gửi tới Admin và Moderator khi cựu sinh viên có yêu cầu hồ sơ mới 
    public function toDatabase(object $notifiable): DatabaseMessage
    {
        $hoTen = $this->alumni->ho_ten;
        $mssv = $this->alumni->mssv;
        $namTotNghiep = $this->kyc->nam_tot_nghiep ?? $this->alumni->nam_tot_nghiep;

        return new DatabaseMessage([
            'title' => 'Có hồ sơ Alumni KYC mới',
            'message' => "Cựu sinh viên {$hoTen} (MSSV: {$mssv}, Khóa tốt nghiệp: {$namTotNghiep}) đã gửi hồ sơ xác thực KYC mới. Vui lòng kiểm tra và phê duyệt.",
            'kyc_id' => $this->kyc->ma_yeu_cau_xac_thuc,
            'ma_nguoi_dung' => $this->alumni->ma_nguoi_dung,
            'mssv' => $mssv,
            'ho_ten' => $hoTen,
            'nam_tot_nghiep' => $namTotNghiep,
            'url' => route('admin.kyc.index'),
        ]);
    }
}

