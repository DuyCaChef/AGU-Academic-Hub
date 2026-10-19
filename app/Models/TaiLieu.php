<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaiLieu extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tai_lieu';
    protected $primaryKey = 'ma_tai_lieu';

    const DELETED_AT = 'ngay_xoa_tam';

    protected $fillable = [
        'ma_tai_lieu_goc',
        'ma_mon_hoc',
        'ma_nguoi_dang',
        'tieu_de',
        'duong_dan_tep',
        'dinh_dang_tep',
        'dung_luong_kb',
        'ma_bam_file',
        'chu_ky_noi_dung',
        'phan_loai',
        'phien_ban',
        'luot_tai',
        'luot_thich',
        'trang_thai',
    ];

    protected function casts(): array
    {
        return [
            'dung_luong_kb' => 'integer',
            'luot_tai' => 'integer',
            'luot_thich' => 'integer',
        ];
    }

    public function monHoc(): BelongsTo
    {
        return $this->belongsTo(MonHoc::class, 'ma_mon_hoc', 'ma_mon_hoc');
    }

    public function nguoiDang(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'ma_nguoi_dang', 'ma_nguoi_dung');
    }

    public function taiLieuGoc(): BelongsTo
    {
        return $this->belongsTo(TaiLieu::class, 'ma_tai_lieu_goc', 'ma_tai_lieu');
    }

    public function phienBans(): HasMany
    {
        return $this->hasMany(TaiLieu::class, 'ma_tai_lieu_goc', 'ma_tai_lieu');
    }

    public function baoCaoViPhams(): HasMany
    {
        return $this->hasMany(BaoCaoViPham::class, 'ma_tai_lieu', 'ma_tai_lieu');
    }
}