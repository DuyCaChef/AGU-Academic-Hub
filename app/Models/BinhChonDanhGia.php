<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BinhChonDanhGia extends Model
{
    use HasFactory;

    protected $table = 'binh_chon_danh_gia';
    protected $primaryKey = 'ma_binh_chon';

    protected $fillable = [
        'ma_danh_gia',
        'ma_nguoi_dung',
        'huu_ich',
        'loai_binh_chon',
        'vote_type',
    ];

    protected function casts(): array
    {
        return [
            'huu_ich' => 'boolean',
            'loai_binh_chon' => 'integer',
        ];
    }

    // Accessor & Mutator cho vote_type (tương thích tiếng Anh / tiếng Việt)
    public function getVoteTypeAttribute(): int
    {
        return (int) ($this->attributes['loai_binh_chon'] ?? 1);
    }

    public function setVoteTypeAttribute($value): void
    {
        $this->attributes['loai_binh_chon'] = (int) $value;
        $this->attributes['huu_ich'] = ((int) $value === 1);
    }

    public function danhGia(): BelongsTo
    {
        return $this->belongsTo(DanhGiaMonHoc::class, 'ma_danh_gia', 'ma_danh_gia');
    }

    public function nguoiDung(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'ma_nguoi_dung', 'ma_nguoi_dung');
    }
}
