<?php

namespace App\Modules\Moderation\Infrastructure\Notifications;

use App\Models\BaoCaoDanhGia;
use App\Models\DanhGiaMonHoc;
use App\Models\NguoiDung;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class NewReviewViolationReportNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly BaoCaoDanhGia $report,
        private readonly DanhGiaMonHoc $review,
        private readonly NguoiDung $reporter,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        $course = $this->review->monHoc;
        $courseTitle = $course ? "{$course->ma_mon_hoc} - {$course->ten_mon_hoc}" : "Môn học #{$this->review->ma_mon_hoc}";
        $reason = $this->report->ly_do;
        $reporterName = $this->reporter->ho_ten ?? 'Người dùng';

        $reasonLabels = [
            'NGON_TU_KICH_DONG' => 'Ngôn từ kích động / xúc phạm',
            'BOI_NHO'           => 'Bôi nhọ danh dự',
            'QUAY_ROI'          => 'Quấy rối',
            'SPAM'              => 'Spam / Quảng cáo',
            'KHAC'              => 'Khác',
        ];

        $reasonText = $reasonLabels[$reason] ?? $reason;

        return new DatabaseMessage([
            'title'         => 'Báo cáo vi phạm đánh giá mới',
            'message'       => "Bài đánh giá môn \"{$courseTitle}\" vừa nhận được báo cáo vi phạm ({$reasonText}) từ {$reporterName}.",
            'report_id'     => $this->report->ma_bao_cao,
            'review_id'     => $this->review->ma_danh_gia,
            'course_title'  => $courseTitle,
            'reporter_id'   => $this->reporter->ma_nguoi_dung,
            'reason'        => $reason,
            'url'           => route('admin.review-reports.show', $this->report->ma_bao_cao),
        ]);
    }
}

