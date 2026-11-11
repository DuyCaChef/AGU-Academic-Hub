<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DapUngYeuCauTaiLieu extends Model
{
    use HasFactory;

    protected $table = 'dap_ung_yeu_cau_tai_lieu';
    protected $primaryKey = 'ma_dap_ung';

    protected $fillable = [
        'ma_yeu_cau',
        'ma_tai_lieu',
        'ma_nguoi_dong_gop',
        'trang_thai',
        'ghi_chu_dong_gop',
        'ly_do_tu_choi_giai_ngan',
        'ly_do_nguoi_tao_tu_choi',
        'nguoi_duyet',
        'thoi_gian_duyet',
    ];

    protected function casts(): array
    {
        return [
            'thoi_gian_duyet' => 'datetime',
        ];
    }

    public function yeuCau(): BelongsTo
    {
        return $this->belongsTo(YeuCauTaiLieu::class, 'ma_yeu_cau', 'ma_yeu_cau');
    }

    public function taiLieu(): BelongsTo
    {
        return $this->belongsTo(TaiLieu::class, 'ma_tai_lieu', 'ma_tai_lieu');
    }

    public function nguoiDongGop(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'ma_nguoi_dong_gop', 'ma_nguoi_dung');
    }

    public function nguoiDuyet(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_duyet', 'ma_nguoi_dung');
    }

    public function yeuCauChinhSuas(): HasMany
    {
        return $this->hasMany(YeuCauChinhSuaDongGop::class, 'ma_dap_ung', 'ma_dap_ung');
    }
}
