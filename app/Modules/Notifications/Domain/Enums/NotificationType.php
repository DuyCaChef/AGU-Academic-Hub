<?php

namespace App\Modules\Notifications\Domain\Enums;

enum NotificationType: string
{
    // Phân hệ Bounty / Wishlist
    case BOUNTY_CONTRIBUTED = 'BOUNTY_CONTRIBUTED';
    case BOUNTY_CONFIRMED = 'BOUNTY_CONFIRMED';
    case BOUNTY_CANCELLED = 'BOUNTY_CANCELLED';
    case NEW_BOUNTY_REQUEST = 'NEW_BOUNTY_REQUEST';

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
            self::NEW_BOUNTY_REQUEST => 'Có yêu cầu tìm tài liệu mới',
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

