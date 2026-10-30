<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BaoCaoViPham extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'bao_cao_vi_pham';

    protected $primaryKey = 'ma_bao_cao';

    const DELETED_AT = 'ngay_xoa_tam';

    protected $fillable = [
        'ma_tai_lieu',
        'ma_nguoi_bao_cao',
        'ly_do',
        'chi_tiet_loi',
        'trang_thai_xu_ly',
        'ma_nguoi_xu_ly',
        'ly_do_xu_ly',
        'xu_ly_luc',
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

    public function nguoiBaoCao(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'ma_nguoi_bao_cao', 'ma_nguoi_dung');
    }

    public function nguoiXuLy(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'ma_nguoi_xu_ly', 'ma_nguoi_dung');
    }

    public function baoCaoLienQuan(): HasMany
    {
        return $this->hasMany(self::class, 'ma_tai_lieu', 'ma_tai_lieu')
            ->where('ma_bao_cao', '!=', $this->ma_bao_cao);
    }

    public function laHopLe(): bool
    {
        return $this->trang_thai_xu_ly !== 'BAC_BO'
            && ($this->nguoiBaoCao?->diem_uy_tin ?? 0) >= config('moderation.trusted_reporter_reputation');
    }
}
