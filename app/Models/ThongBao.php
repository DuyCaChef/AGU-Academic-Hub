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
        'data'       => 'array',
        'read_at'    => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $notification): void {
            // Đồng bộ loai từ type nếu Laravel DatabaseChannel truyền type
            if (empty($notification->attributes['loai']) && !empty($notification->attributes['type'])) {
                $notification->attributes['loai'] = $notification->attributes['type'];
                unset($notification->attributes['type']);
            }

            // Đồng bộ du_lieu từ data nếu Laravel DatabaseChannel truyền data
            if (empty($notification->attributes['du_lieu']) && !empty($notification->attributes['data'])) {
                $dataValue = $notification->attributes['data'];
                $notification->attributes['du_lieu'] = is_array($dataValue) ? json_encode($dataValue) : $dataValue;
                unset($notification->attributes['data']);
            }

            // Đồng bộ da_doc_luc từ read_at nếu có
            if (empty($notification->attributes['da_doc_luc']) && !empty($notification->attributes['read_at'])) {
                $notification->attributes['da_doc_luc'] = $notification->attributes['read_at'];
                unset($notification->attributes['read_at']);
            }

            // Trích xuất tieu_de và noi_dung từ du_lieu nếu chưa có
            $duLieu = $notification->du_lieu;
            if (is_string($duLieu)) {
                $duLieu = json_decode($duLieu, true) ?: [];
            }
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

    // --- Type / Loai ---
    public function setLoaiAttribute($value): void
    {
        $this->attributes['loai'] = $value;
    }

    public function setTypeAttribute($value): void
    {
        $this->attributes['loai'] = $value;
    }

    public function getTypeAttribute(): ?string
    {
        return $this->attributes['loai'] ?? null;
    }

    // --- Title / Tieu De ---
    public function setTieuDeAttribute($value): void
    {
        $this->attributes['tieu_de'] = $value;
    }

    public function setTitleAttribute($value): void
    {
        $this->attributes['tieu_de'] = $value;
    }

    public function getTitleAttribute(): ?string
    {
        return $this->attributes['tieu_de'] ?? $this->data['title'] ?? null;
    }

    public function getTieuDeAttribute(): string
    {
        return $this->attributes['tieu_de'] ?? $this->data['title'] ?? 'Thông báo hệ thống';
    }

    // --- Message / Noi Dung ---
    public function setNoiDungAttribute($value): void
    {
        $this->attributes['noi_dung'] = $value;
    }

    public function setMessageAttribute($value): void
    {
        $this->attributes['noi_dung'] = $value;
    }

    public function getMessageAttribute(): ?string
    {
        return $this->attributes['noi_dung'] ?? $this->data['message'] ?? null;
    }

    public function getNoiDungAttribute(): string
    {
        return $this->attributes['noi_dung'] ?? $this->data['message'] ?? '';
    }

    // --- Data / Du Lieu ---
    public function setDuLieuAttribute($value): void
    {
        $this->attributes['du_lieu'] = is_array($value) ? json_encode($value) : $value;
    }

    public function setDataAttribute($value): void
    {
        $this->attributes['du_lieu'] = is_array($value) ? json_encode($value) : $value;
    }

    public function getDuLieuAttribute(): array
    {
        $val = $this->attributes['du_lieu'] ?? null;
        if (is_array($val)) {
            return $val;
        }
        return json_decode($val ?? '{}', true) ?: [];
    }

    public function getDataAttribute(): array
    {
        return $this->getDuLieuAttribute();
    }

    // --- Read At / Da Doc Luc ---
    public function setDaDocLucAttribute($value): void
    {
        $this->attributes['da_doc_luc'] = $value;
    }

    public function setReadAtAttribute($value): void
    {
        $this->attributes['da_doc_luc'] = $value;
    }

    public function getDaDocLucAttribute()
    {
        return $this->asDateTime($this->attributes['da_doc_luc'] ?? null);
    }

    public function getReadAtAttribute()
    {
        return $this->getDaDocLucAttribute();
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
