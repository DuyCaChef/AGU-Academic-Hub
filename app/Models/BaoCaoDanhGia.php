<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BaoCaoDanhGia extends Model
{
    use HasFactory;

    protected $table = 'bao_cao_danh_gia';
    protected $primaryKey = 'ma_bao_cao';

    protected $fillable = [
        'ma_danh_gia',
        'ma_nguoi_bao_cao',
        'ly_do',
        'chi_tiet',
        'trang_thai',
        'nguoi_xu_ly',
        'thoi_gian_xu_ly',
        'ghi_chu_xu_ly',
    ];

    protected function casts(): array
    {
        return [
            'thoi_gian_xu_ly' => 'datetime',
        ];
    }

    public function danhGia(): BelongsTo
    {
        return $this->belongsTo(DanhGiaMonHoc::class, 'ma_danh_gia', 'ma_danh_gia');
    }

    public function nguoiBaoCao(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'ma_nguoi_bao_cao', 'ma_nguoi_dung');
    }

    public function nguoiXuLy(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_xu_ly', 'ma_nguoi_dung');
    }
}
