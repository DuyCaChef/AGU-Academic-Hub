<?php

namespace App\Modules\Notifications\Presentation\Http\Resources;

use App\Models\ThongBao;
use App\Modules\Notifications\Application\DTOs\NotificationPayload;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ThongBao
 */
class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = is_array($this->du_lieu) ? $this->du_lieu : (json_decode($this->du_lieu ?? '[]', true) ?: []);

        // Xác định loại thông báo và nhóm
        $type = $data['type'] ?? class_basename($this->loai ?? $this->type);
        $category = $data['category'] ?? 'PERSONAL';

        // Lấy URL an toàn (chống open redirect)
        $rawUrl = $data['action_url'] ?? $data['url'] ?? null;
        $safeUrl = NotificationPayload::sanitizeActionUrl($rawUrl);

        return [
            'id'               => (string) $this->id,
            'type'             => $type,
            'category'         => $category,
            'title'            => (string) ($this->tieu_de ?? $data['title'] ?? 'Thông báo hệ thống'),
            'message'          => (string) ($this->noi_dung ?? $data['message'] ?? ''),
            'target_type'      => $data['target_type'] ?? null,
            'target_id'        => $data['target_id'] ?? null,
            'url'              => $safeUrl,
            'action_url'       => $safeUrl,
            'read_at'          => $this->da_doc_luc?->toIso8601String(),
            'is_read'          => $this->read(),
            'created_at'       => $this->created_at?->toIso8601String(),
            'created_at_human' => $this->created_at?->diffForHumans() ?? 'Vừa xong',
            'metadata'         => is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        ];
    }
}

