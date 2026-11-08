<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification as BaseDatabaseNotification;

class ThongBao extends BaseDatabaseNotification
{
    protected $table = 'notifications';

    protected $guarded = [];

    protected $casts = [
        'du_lieu'    => 'array',
        'da_doc_luc' => 'datetime',
    ];

    public function setAttribute($key, $value)
    {
        if ($key === 'type') {
            $this->attributes['loai'] = $value;
            return $this;
        }
        if ($key === 'title') {
            $this->attributes['tieu_de'] = $value;
            return $this;
        }
        if ($key === 'message') {
            $this->attributes['noi_dung'] = $value;
            return $this;
        }
        if ($key === 'data') {
            $this->attributes['du_lieu'] = is_array($value) ? json_encode($value) : $value;
            return $this;
        }
        if ($key === 'read_at') {
            $this->attributes['da_doc_luc'] = $value;
            return $this;
        }

        return parent::setAttribute($key, $value);
    }

    public function getAttribute($key)
    {
        if ($key === 'type') {
            return $this->attributes['loai'] ?? null;
        }
        if ($key === 'title' || $key === 'tieu_de') {
            return $this->attributes['tieu_de'] ?? $this->data['title'] ?? 'Thông báo hệ thống';
        }
        if ($key === 'message' || $key === 'noi_dung') {
            return $this->attributes['noi_dung'] ?? $this->data['message'] ?? '';
        }
        if ($key === 'data' || $key === 'du_lieu') {
            $val = $this->attributes['du_lieu'] ?? null;
            if (is_array($val)) {
                return $val;
            }
            return json_decode($val ?? '{}', true) ?: [];
        }
        if ($key === 'read_at' || $key === 'da_doc_luc') {
            $val = $this->attributes['da_doc_luc'] ?? null;
            return $val ? $this->asDateTime($val) : null;
        }

        return parent::getAttribute($key);
    }

    protected static function booted(): void
    {
        static::creating(function (self $notification): void {
            // Tự động trích xuất tieu_de và noi_dung từ du_lieu nếu chưa có
            $duLieu = $notification->du_lieu;
            if (is_array($duLieu)) {
                if (empty($notification->attributes['tieu_de']) && isset($duLieu['title'])) {
                    $notification->attributes['tieu_de'] = (string) $duLieu['title'];
                }
                if (empty($notification->attributes['noi_dung']) && isset($duLieu['message'])) {
                    $notification->attributes['noi_dung'] = (string) $duLieu['message'];
                }
            }
        });
    }

    // Đường dẫn liên kết của thông báo
    public function getDuongDanAttribute(): ?string
    {
        return $this->data['action_url'] ?? $this->data['url'] ?? null;
    }

    // Kiểm tra thông báo chưa đọc
    public function getLaChuaDocAttribute(): bool
    {
        return $this->unread();
    }

    public function markAsRead(): void
    {
        if (is_null($this->attributes['da_doc_luc'] ?? null)) {
            $this->forceFill(['da_doc_luc' => $this->freshTimestamp()])->save();
        }
    }

    public function markAsUnread(): void
    {
        if (!is_null($this->attributes['da_doc_luc'] ?? null)) {
            $this->forceFill(['da_doc_luc' => null])->save();
        }
    }

    public function read(): bool
    {
        return !is_null($this->attributes['da_doc_luc'] ?? null);
    }

    public function unread(): bool
    {
        return is_null($this->attributes['da_doc_luc'] ?? null);
    }

    public function scopeRead(Builder $query): Builder
    {
        return $query->whereNotNull('da_doc_luc');
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('da_doc_luc');
    }
}
