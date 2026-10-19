<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BaoCaoViPham extends Model
{
    use HasFactory;

    protected $table = 'bao_cao_vi_pham';
    protected $primaryKey = 'ma_bao_cao';

    protected $fillable = [
        'ma_tai_lieu',
        'ma_nguoi_bao_cao',
        'ly_do',
        'chi_tiet_loi',
        'trang_thai_xu_ly',
    ];

    public function taiLieu(): BelongsTo
    {
        return $this->belongsTo(TaiLieu::class, 'ma_tai_lieu', 'ma_tai_lieu');
    }

    public function nguoiBaoCao(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'ma_nguoi_bao_cao', 'ma_nguoi_dung');
    }
}
