<?php

namespace App\Modules\Notifications\Application\DTOs;

use App\Modules\Notifications\Domain\Enums\NotificationCategory;
use App\Modules\Notifications\Domain\Enums\NotificationType;

class NotificationPayload
{
    public readonly ?string $actionUrl;

    public function __construct(
        public readonly NotificationType $type,
        public readonly NotificationCategory $category,
        public readonly string $title,
        public readonly string $message,
        public readonly ?string $targetType = null,
        public readonly ?string $targetId = null,
        ?string $actionUrl = null,
        public readonly array $metadata = [],
        public readonly ?string $idempotencyKey = null,
    ) {
        $this->actionUrl = self::sanitizeActionUrl($actionUrl);
    }

    // Làm sạch và kiểm tra URL an toàn (chỉ chấp nhận đường dẫn nội bộ)
    public static function sanitizeActionUrl(?string $url): ?string
    {
        if (empty($url)) {
            return null;
        }

        $trimmed = trim($url);

        // Chống open redirect: Chỉ cho phép đường dẫn tương đối bắt đầu bằng /
        // Từ chối giao thức tuyệt đối (http:, https:, javascript:) và protocol-relative (//)
        if (str_starts_with($trimmed, '//') || preg_match('/^[a-z0-9+.-]+:/i', $trimmed)) {
            return null;
        }

        return str_starts_with($trimmed, '/') ? $trimmed : '/' . $trimmed;
    }

    // Chuyển đổi thành mảng lưu trữ trong trường data của notification
    public function toArray(): array
    {
        return [
            'type'            => $this->type->value,
            'category'        => $this->category->value,
            'title'           => $this->title,
            'message'         => $this->message,
            'target_type'     => $this->targetType,
            'target_id'       => $this->targetId,
            'action_url'      => $this->actionUrl,
            'url'             => $this->actionUrl, // Tương thích ngược với ThongBao::getDuongDanAttribute()
            'metadata'        => $this->metadata,
            'idempotency_key' => $this->idempotencyKey,
        ];
    }
}

