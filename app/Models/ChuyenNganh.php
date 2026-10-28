<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChuyenNganh extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'chuyen_nganh';
    protected $primaryKey = 'ma_chuyen_nganh';

    const DELETED_AT = 'ngay_xoa_tam';

    protected $fillable = [
        'ma_khoa',
        'ky_hieu_nganh',
        'ten_chuyen_nganh',
        'mo_ta',
    ];

    /**
     * Luôn hiển thị ký hiệu Chuyên ngành ở dạng chữ in hoa, kể cả dữ liệu cũ.
     */
    public function getKyHieuNganhAttribute(?string $value): ?string
    {
        return $value === null ? null : mb_strtoupper($value);
    }

    public function khoa(): BelongsTo
    {
        return $this->belongsTo(Khoa::class, 'ma_khoa', 'ma_khoa');
    }

    public function monHocs(): BelongsToMany
    {
        return $this->belongsToMany(MonHoc::class, 'mon_hoc_chuyen_nganh', 'ma_chuyen_nganh', 'ma_mon_hoc')
            ->withTimestamps();
    }
}
