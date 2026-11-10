<?php

namespace App\Modules\Notifications\Domain\Services;

use App\Models\DapUngYeuCauTaiLieu;
use App\Models\TaiLieu;
use App\Models\YeuCauTaiLieu;
use App\Models\YeuCauXacThuc;
use App\Modules\Notifications\Application\DTOs\NotificationPayload;
use App\Modules\Notifications\Domain\Enums\NotificationCategory;
use App\Modules\Notifications\Domain\Enums\NotificationType;

class NotificationPayloadBuilder
{
    // UC-ST-01: Có người đóng góp tài liệu cho Bounty
    public function bountyContributed(YeuCauTaiLieu $yeuCau, DapUngYeuCauTaiLieu $dapUng, bool $isPendingApproval): NotificationPayload
    {
        $docTitle = $dapUng->taiLieu?->tieu_de ?? 'Tài liệu đóng góp';
        $message = $isPendingApproval
            ? "Có sinh viên đã đóng góp tài liệu \"{$docTitle}\" cho yêu cầu \"{$yeuCau->tieu_de}\" của bạn. Tài liệu đang chờ Ban kiểm duyệt phê duyệt trước khi bạn có thể nghiệm thu."
            : "Có sinh viên đã đóng góp tài liệu \"{$docTitle}\" cho yêu cầu \"{$yeuCau->tieu_de}\" của bạn. Tài liệu đã được kiểm duyệt và sẵn sàng để bạn nghiệm thu.";

        return new NotificationPayload(
            type: NotificationType::BOUNTY_CONTRIBUTED,
            category: NotificationCategory::PERSONAL,
            title: 'Có tài liệu đóng góp cho yêu cầu tìm kiếm',
            message: $message,
            targetType: 'bounty',
            targetId: (string) $yeuCau->ma_yeu_cau,
            actionUrl: "/yeu-cau-tai-lieu/{$yeuCau->ma_yeu_cau}",
            metadata: [
                'bounty_id'           => $yeuCau->ma_yeu_cau,
                'submission_id'       => $dapUng->ma_dap_ung,
                'is_pending_approval' => $isPendingApproval,
            ],
            idempotencyKey: "bounty_contrib_{$yeuCau->ma_yeu_cau}_{$dapUng->ma_dap_ung}"
        );
    }

    // UC-ST-04: Người tạo xác nhận nghiệm thu tài liệu Bounty
    public function bountyConfirmedRequester(YeuCauTaiLieu $yeuCau, int $points): NotificationPayload
    {
        return new NotificationPayload(
            type: NotificationType::BOUNTY_CONFIRMED,
            category: NotificationCategory::PERSONAL,
            title: 'Nghiệm thu yêu cầu tài liệu thành công',
            message: "Bạn đã xác nhận nghiệm thu tài liệu cho yêu cầu \"{$yeuCau->tieu_de}\". {$points} điểm thưởng đã được giải ngân thành công.",
            targetType: 'bounty',
            targetId: (string) $yeuCau->ma_yeu_cau,
            actionUrl: "/yeu-cau-tai-lieu/{$yeuCau->ma_yeu_cau}",
            metadata: ['bounty_id' => $yeuCau->ma_yeu_cau, 'points' => $points],
            idempotencyKey: "bounty_confirm_req_{$yeuCau->ma_yeu_cau}"
        );
    }

    // UC-ST-05: Người đóng góp nhận điểm giải ngân Bounty
    public function bountyConfirmedContributor(YeuCauTaiLieu $yeuCau, int $points): NotificationPayload
    {
        return new NotificationPayload(
            type: NotificationType::BOUNTY_CONFIRMED,
            category: NotificationCategory::PERSONAL,
            title: 'Bạn đã nhận được điểm thưởng Bounty',
            message: "Tài liệu đóng góp của bạn cho yêu cầu \"{$yeuCau->tieu_de}\" đã được người tạo nghiệm thu! Bạn nhận được +{$points} điểm thưởng và điểm uy tín.",
            targetType: 'bounty',
            targetId: (string) $yeuCau->ma_yeu_cau,
            actionUrl: "/yeu-cau-tai-lieu/{$yeuCau->ma_yeu_cau}",
            metadata: ['bounty_id' => $yeuCau->ma_yeu_cau, 'points' => $points],
            idempotencyKey: "bounty_confirm_contrib_{$yeuCau->ma_yeu_cau}"
        );
    }

    // UC-ST-06: Yêu cầu Bounty bị hủy (Gửi cho người đã nộp)
    public function bountyCancelledContributor(YeuCauTaiLieu $yeuCau): NotificationPayload
    {
        return new NotificationPayload(
            type: NotificationType::BOUNTY_CANCELLED,
            category: NotificationCategory::PERSONAL,
            title: 'Yêu cầu tài liệu đã bị hủy',
            message: "Yêu cầu tìm tài liệu \"{$yeuCau->tieu_de}\" mà bạn từng đóng góp đã bị người tạo hủy bỏ.",
            targetType: 'bounty',
            targetId: (string) $yeuCau->ma_yeu_cau,
            actionUrl: "/yeu-cau-tai-lieu/{$yeuCau->ma_yeu_cau}",
            metadata: ['bounty_id' => $yeuCau->ma_yeu_cau],
            idempotencyKey: "bounty_cancel_contrib_{$yeuCau->ma_yeu_cau}"
        );
    }

    // UC-ST-07: Hoàn điểm cọc Bounty cho người tạo
    public function bountyRefundedRequester(YeuCauTaiLieu $yeuCau, int $points): NotificationPayload
    {
        return new NotificationPayload(
            type: NotificationType::BOUNTY_CANCELLED,
            category: NotificationCategory::PERSONAL,
            title: 'Đã hoàn trả điểm cọc yêu cầu tài liệu',
            message: "Bạn đã hủy yêu cầu \"{$yeuCau->tieu_de}\". {$points} điểm thưởng tạm giữ đã được hoàn trả vào ví điểm khả dụng.",
            targetType: 'bounty',
            targetId: (string) $yeuCau->ma_yeu_cau,
            actionUrl: "/yeu-cau-tai-lieu/{$yeuCau->ma_yeu_cau}",
            metadata: ['bounty_id' => $yeuCau->ma_yeu_cau, 'refunded_points' => $points],
            idempotencyKey: "bounty_refund_req_{$yeuCau->ma_yeu_cau}"
        );
    }

    // UC-ST-02 & UC-ST-08: Tài liệu upload được duyệt
    public function documentApproved(TaiLieu $document): NotificationPayload
    {
        return new NotificationPayload(
            type: NotificationType::DOCUMENT_APPROVED,
            category: NotificationCategory::PERSONAL,
            title: 'Tài liệu của bạn đã được phê duyệt',
            message: "Tài liệu \"{$document->tieu_de}\" đã được Ban kiểm duyệt phê duyệt và hiển thị công khai trên hệ thống.",
            targetType: 'document',
            targetId: (string) $document->ma_tai_lieu,
            actionUrl: "/tai-lieu/{$document->ma_tai_lieu}",
            metadata: ['document_id' => $document->ma_tai_lieu],
            idempotencyKey: "doc_approved_{$document->ma_tai_lieu}"
        );
    }

    // UC-ST-02: Tài liệu đóng góp Bounty được duyệt (báo cho Requester)
    public function bountyDocumentApprovedForRequester(YeuCauTaiLieu $yeuCau, TaiLieu $document): NotificationPayload
    {
        return new NotificationPayload(
            type: NotificationType::DOCUMENT_APPROVED,
            category: NotificationCategory::PERSONAL,
            title: 'Tài liệu đóng góp săn thưởng đã được duyệt',
            message: "Tài liệu đóng góp \"{$document->tieu_de}\" cho yêu cầu \"{$yeuCau->tieu_de}\" của bạn đã được Ban kiểm duyệt thông qua. Bạn có thể vào nghiệm thu ngay.",
            targetType: 'bounty',
            targetId: (string) $yeuCau->ma_yeu_cau,
            actionUrl: "/yeu-cau-tai-lieu/{$yeuCau->ma_yeu_cau}",
            metadata: ['bounty_id' => $yeuCau->ma_yeu_cau, 'document_id' => $document->ma_tai_lieu],
            idempotencyKey: "bounty_doc_appr_req_{$yeuCau->ma_yeu_cau}_{$document->ma_tai_lieu}"
        );
    }

    // UC-ST-03 & UC-ST-08: Tài liệu bị từ chối
    public function documentRejected(TaiLieu $document, ?string $reason): NotificationPayload
    {
        $reasonText = !empty($reason) ? " Lý do: {$reason}" : '';
        return new NotificationPayload(
            type: NotificationType::DOCUMENT_REJECTED,
            category: NotificationCategory::PERSONAL,
            title: 'Tài liệu không đạt yêu cầu kiểm duyệt',
            message: "Tài liệu \"{$document->tieu_de}\" đã bị Ban kiểm duyệt từ chối.{$reasonText}",
            targetType: 'document',
            targetId: (string) $document->ma_tai_lieu,
            actionUrl: "/tai-lieu/cua-toi",
            metadata: ['document_id' => $document->ma_tai_lieu, 'reason' => $reason],
            idempotencyKey: "doc_rejected_{$document->ma_tai_lieu}"
        );
    }

    // UC-MOD-06: Có tài liệu Bounty mới cần kiểm duyệt
    public function bountyDocumentReviewRequired(YeuCauTaiLieu $yeuCau, TaiLieu $document): NotificationPayload
    {
        return new NotificationPayload(
            type: NotificationType::DOCUMENT_REVIEW_REQUIRED,
            category: NotificationCategory::WORK_ITEM,
            title: 'Tài liệu đóng góp săn thưởng cần kiểm duyệt',
            message: "Tài liệu \"{$document->tieu_de}\" được nộp cho yêu cầu săn thưởng \"{$yeuCau->tieu_de}\" đang chờ Ban kiểm duyệt thẩm định.",
            targetType: 'document',
            targetId: (string) $document->ma_tai_lieu,
            actionUrl: "/quan-tri/tai-lieu/cho-kiem-tra",
            metadata: ['document_id' => $document->ma_tai_lieu, 'bounty_id' => $yeuCau->ma_yeu_cau],
            idempotencyKey: "bounty_mod_req_{$document->ma_tai_lieu}"
        );
    }

    // UC-MOD-03: Có đánh giá môn học bị báo cáo
    public function reviewReported(int $reviewId, string $courseName, int $reportCount): NotificationPayload
    {
        return new NotificationPayload(
            type: NotificationType::REVIEW_REPORTED,
            category: NotificationCategory::WORK_ITEM,
            title: 'Đánh giá môn học bị báo cáo vi phạm',
            message: "Đánh giá môn học \"{$courseName}\" đã nhận được {$reportCount} báo cáo vi phạm cần kiểm tra.",
            targetType: 'review',
            targetId: (string) $reviewId,
            actionUrl: "/quan-tri/danh-gia-mon-hoc",
            metadata: ['review_id' => $reviewId, 'report_count' => $reportCount],
            idempotencyKey: "review_rep_{$reviewId}_{$reportCount}"
        );
    }

    // UC-MOD-07 & UC-ADM-01: Hồ sơ KYC cựu sinh viên cần xử lý
    public function kycSubmitted(YeuCauXacThuc $kyc, string $applicantName): NotificationPayload
    {
        return new NotificationPayload(
            type: NotificationType::KYC_SUBMITTED,
            category: NotificationCategory::WORK_ITEM,
            title: 'Hồ sơ KYC cựu sinh viên mới cần xét duyệt',
            message: "Cựu sinh viên \"{$applicantName}\" đã gửi hồ sơ xác thực danh tính KYC cần phê duyệt.",
            targetType: 'kyc',
            targetId: (string) $kyc->ma_yeu_cau_xac_thuc,
            actionUrl: "/quan-tri/yeu-cau-xac-thuc",
            metadata: ['kyc_id' => $kyc->ma_yeu_cau_xac_thuc],
            idempotencyKey: "kyc_sub_{$kyc->ma_yeu_cau_xac_thuc}"
        );
    }

    // UC-ST: Kết quả phê duyệt / từ chối KYC cho Cựu sinh viên
    public function kycDecision(YeuCauXacThuc $kyc, bool $approved, ?string $reason): NotificationPayload
    {
        $title = $approved ? 'Hồ sơ KYC đã được phê duyệt' : 'Hồ sơ KYC đã bị từ chối';
        $message = $approved
            ? 'Hồ sơ cựu sinh viên của bạn đã được duyệt thành công! Tài khoản của bạn đã được kích hoạt và cấp 100 điểm thưởng ban đầu.'
            : 'Hồ sơ cựu sinh viên của bạn đã bị từ chối.' . (!empty($reason) ? " Lý do: {$reason}" : '');

        return new NotificationPayload(
            type: $approved ? NotificationType::KYC_APPROVED : NotificationType::KYC_REJECTED,
            category: NotificationCategory::PERSONAL,
            title: $title,
            message: $message,
            targetType: 'kyc',
            targetId: (string) $kyc->ma_yeu_cau_xac_thuc,
            actionUrl: $approved ? "/trang-chu" : "/xac-thuc-cuu-sinh-vien",
            metadata: ['kyc_id' => $kyc->ma_yeu_cau_xac_thuc, 'approved' => $approved],
            idempotencyKey: "kyc_dec_{$kyc->ma_yeu_cau_xac_thuc}"
        );
    }
}

