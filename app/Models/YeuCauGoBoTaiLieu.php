<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YeuCauGoBoTaiLieu extends Model
{
    use HasFactory;

    protected $table = 'yeu_cau_go_bo_tai_lieu';

    protected $primaryKey = 'ma_yeu_cau_go_bo';

    protected $fillable = [
        'ma_tai_lieu',
        'ma_nguoi_yeu_cau',
        'ly_do',
        'trang_thai',
        'ma_nguoi_xu_ly',
        'xu_ly_luc',
        'ly_do_tu_choi',
    ];

    protected function casts(): array
    {
        return [
            'xu_ly_luc' => 'datetime',
        ];
    }

    public function taiLieu(): BelongsTo
    {
        return $this->belongsTo(TaiLieu::class, 'ma_tai_lieu', 'ma_tai_lieu');
    }

    public function nguoiYeuCau(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'ma_nguoi_yeu_cau', 'ma_nguoi_dung');
    }

    public function nguoiXuLy(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'ma_nguoi_xu_ly', 'ma_nguoi_dung');
    }
}
