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
    ];

    protected function casts(): array
    {
        return [
            'huu_ich' => 'boolean',
        ];
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
