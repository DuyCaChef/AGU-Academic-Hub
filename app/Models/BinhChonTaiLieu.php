<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BinhChonTaiLieu extends Model
{
    use HasFactory;

    protected $table = 'binh_chon_tai_lieu';

    protected $primaryKey = 'ma_binh_chon';

    protected $fillable = [
        'ma_tai_lieu',
        'ma_nguoi_dung',
    ];

    public function taiLieu(): BelongsTo
    {
        return $this->belongsTo(TaiLieu::class, 'ma_tai_lieu', 'ma_tai_lieu');
    }

    public function nguoiDung(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'ma_nguoi_dung', 'ma_nguoi_dung');
    }
}

