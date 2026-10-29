<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LichSuPhanQuyenNguoiDung extends Model
{
    protected $table = 'lich_su_phan_quyen_nguoi_dung';

    protected $fillable = [
        'ma_nguoi_dung',
        'ma_nguoi_thuc_hien',
        'vai_tro_cu',
        'vai_tro_moi',
        'cap_quan_tri_cu',
        'cap_quan_tri_moi',
        'ma_khoa_cu',
        'ma_khoa_moi',
        'ly_do',
    ];

    public function nguoiThucHien(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'ma_nguoi_thuc_hien', 'ma_nguoi_dung');
    }
}
