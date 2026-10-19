<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MonHoc extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mon_hoc';
    protected $primaryKey = 'ma_mon_hoc';

    const DELETED_AT = 'ngay_xoa_tam';

    protected $fillable = [
        'ma_khoa',
        'ma_hoc_phan',
        'ten_mon_hoc',
        'so_tin_chi',
        'mo_ta',
    ];

    protected function casts(): array
    {
        return [
            'so_tin_chi' => 'integer',
        ];
    }

    public function khoa(): BelongsTo
    {
        return $this->belongsTo(Khoa::class, 'ma_khoa', 'ma_khoa');
    }

    public function chuyenNganhs(): BelongsToMany
    {
        return $this->belongsToMany(ChuyenNganh::class, 'mon_hoc_chuyen_nganh', 'ma_mon_hoc', 'ma_chuyen_nganh')
            ->withTimestamps();
    }

    public function taiLieus(): HasMany
    {
        return $this->hasMany(TaiLieu::class, 'ma_mon_hoc', 'ma_mon_hoc');
    }

    public function danhGiaMonHocs(): HasMany
    {
        return $this->hasMany(DanhGiaMonHoc::class, 'ma_mon_hoc', 'ma_mon_hoc');
    }

    public function yeuCauTaiLieus(): HasMany
    {
        return $this->hasMany(YeuCauTaiLieu::class, 'ma_mon_hoc', 'ma_mon_hoc');
    }
}