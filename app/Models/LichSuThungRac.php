<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LichSuThungRac extends Model
{
    protected $table = 'lich_su_thung_rac';

    protected $fillable = [
        'ma_tai_lieu',
        'ma_nguoi_thuc_hien',
        'hanh_dong',
        'nguon',
        'ly_do',
        'chi_tiet',
    ];

    protected function casts(): array
    {
        return ['chi_tiet' => 'array'];
    }

    public function nguoiThucHien(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'ma_nguoi_thuc_hien', 'ma_nguoi_dung');
    }
}
