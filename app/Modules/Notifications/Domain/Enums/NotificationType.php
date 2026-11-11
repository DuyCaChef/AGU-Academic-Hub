<?php

namespace App\Modules\Notifications\Domain\Enums;

enum NotificationType: string
{
    // Phân hệ Bounty / Wishlist
    case BOUNTY_CONTRIBUTED = 'BOUNTY_CONTRIBUTED';
    case BOUNTY_CONFIRMED = 'BOUNTY_CONFIRMED';
    case BOUNTY_CANCELLED = 'BOUNTY_CANCELLED';
    case BOUNTY_REJECTED = 'BOUNTY_REJECTED';
    case NEW_BOUNTY_REQUEST = 'NEW_BOUNTY_REQUEST';
    case CORRECTION_REQUESTED = 'CORRECTION_REQUESTED';
    case CORRECTION_ACCEPTED = 'CORRECTION_ACCEPTED';
    case CORRECTION_REJECTED = 'CORRECTION_REJECTED';
    case CORRECTION_REPLACEMENT_SUBMITTED = 'CORRECTION_REPLACEMENT_SUBMITTED';
    case CORRECTION_COMPLETED = 'CORRECTION_COMPLETED';

    // Phân hệ Documents
    case DOCUMENT_APPROVED = 'DOCUMENT_APPROVED';
    case DOCUMENT_REJECTED = 'DOCUMENT_REJECTED';
    case DOCUMENT_REVIEW_REQUIRED = 'DOCUMENT_REVIEW_REQUIRED';
    case DOCUMENT_REPORTED = 'DOCUMENT_REPORTED';
    case DOCUMENT_REMOVAL_REQUESTED = 'DOCUMENT_REMOVAL_REQUESTED';
    case DOCUMENT_REMOVAL_RESOLVED = 'DOCUMENT_REMOVAL_RESOLVED';

    // Phân hệ Reviews
    case REVIEW_REPORTED = 'REVIEW_REPORTED';
    case REVIEW_MODERATED = 'REVIEW_MODERATED';

    // Phân hệ Identity & KYC
    case KYC_SUBMITTED = 'KYC_SUBMITTED';
    case KYC_APPROVED = 'KYC_APPROVED';
    case KYC_REJECTED = 'KYC_REJECTED';
    case ACCOUNT_STATUS_CHANGED = 'ACCOUNT_STATUS_CHANGED';

    // Hệ thống chung
    case SYSTEM_ALERT = 'SYSTEM_ALERT';

    public function label(): string
    {
        return match ($this) {
            self::BOUNTY_CONTRIBUTED => 'Có đóng góp tài liệu săn thưởng',
            self::BOUNTY_CONFIRMED => 'Nghiệm thu săn thưởng thành công',
            self::BOUNTY_CANCELLED => 'Yêu cầu săn thưởng đã bị hủy',
            self::BOUNTY_REJECTED => 'Bài đóng góp săn thưởng bị từ chối',
            self::NEW_BOUNTY_REQUEST => 'Có yêu cầu tìm tài liệu mới',
            self::CORRECTION_REQUESTED => 'Có yêu cầu chỉnh sửa tài liệu',
            self::CORRECTION_ACCEPTED => 'Yêu cầu chỉnh sửa được chấp nhận',
            self::CORRECTION_REJECTED => 'Yêu cầu chỉnh sửa bị từ chối',
            self::CORRECTION_REPLACEMENT_SUBMITTED => 'Có phiên bản tài liệu thay thế mới',
            self::CORRECTION_COMPLETED => 'Hoàn tất nghiệm thu chỉnh sửa tài liệu',
            self::DOCUMENT_APPROVED => 'Tài liệu đã được phê duyệt',
            self::DOCUMENT_REJECTED => 'Tài liệu bị từ chối kiểm duyệt',
            self::DOCUMENT_REVIEW_REQUIRED => 'Tài liệu cần kiểm duyệt',
            self::DOCUMENT_REPORTED => 'Tài liệu bị báo cáo vi phạm',
            self::DOCUMENT_REMOVAL_REQUESTED => 'Có yêu cầu gỡ bỏ tài liệu',
            self::DOCUMENT_REMOVAL_RESOLVED => 'Yêu cầu gỡ bỏ đã được xử lý',
            self::REVIEW_REPORTED => 'Đánh giá môn học bị báo cáo',
            self::REVIEW_MODERATED => 'Đánh giá đã được xử lý',
            self::KYC_SUBMITTED => 'Hồ sơ KYC mới cần xét duyệt',
            self::KYC_APPROVED => 'Hồ sơ KYC đã được phê duyệt',
            self::KYC_REJECTED => 'Hồ sơ KYC bị từ chối',
            self::ACCOUNT_STATUS_CHANGED => 'Trạng thái tài khoản đã thay đổi',
            self::SYSTEM_ALERT => 'Cảnh báo vận hành hệ thống',
        };
    }
}

