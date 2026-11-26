<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LichSuTrangThaiNguoiDung extends Model
{
    protected $table = 'lich_su_trang_thai_nguoi_dung';

    protected $fillable = [
        'ma_nguoi_dung',
        'ma_nguoi_thuc_hien',
        'trang_thai_cu',
        'trang_thai_moi',
        'ly_do',
        'hanh_dong',
    ];

    public function nguoiDung(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'ma_nguoi_dung', 'ma_nguoi_dung');
    }

    public function nguoiThucHien(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'ma_nguoi_thuc_hien', 'ma_nguoi_dung');
    }
}
