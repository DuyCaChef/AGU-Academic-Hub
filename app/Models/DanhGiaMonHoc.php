<?php

namespace App\Models;

use App\Enums\TrangThaiDanhGiaMonHoc;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DanhGiaMonHoc extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'danh_gia_mon_hoc';
    protected $primaryKey = 'ma_danh_gia';

    const DELETED_AT = 'ngay_xoa_tam';

    protected $fillable = [
        'ma_mon_hoc',
        'ma_nguoi_dung',
        'hoc_ky',
        'diem_do_kho',
        'diem_thuc_tien',
        'diem_khoi_luong',
        'noi_dung',
        'an_danh',
        'trang_thai',
    ];

    protected function casts(): array
    {
        return [
            'diem_do_kho' => 'integer',
            'diem_thuc_tien' => 'integer',
            'diem_khoi_luong' => 'integer',
            'an_danh' => 'boolean',
            'trang_thai' => TrangThaiDanhGiaMonHoc::class,
        ];
    }

    public function monHoc(): BelongsTo
    {
        return $this->belongsTo(MonHoc::class, 'ma_mon_hoc', 'ma_mon_hoc');
    }

    public function nguoiDung(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'ma_nguoi_dung', 'ma_nguoi_dung');
    }

    public function binhChons(): HasMany
    {
        return $this->hasMany(BinhChonDanhGia::class, 'ma_danh_gia', 'ma_danh_gia');
    }

    public function baoCaos(): HasMany
    {
        return $this->hasMany(BaoCaoDanhGia::class, 'ma_danh_gia', 'ma_danh_gia');
    }

    // Scope lấy đánh giá đã được duyệt hiển thị công khai
    public function scopeDaDuyet($query)
    {
        return $query->where('trang_thai', TrangThaiDanhGiaMonHoc::DA_DUYET);
    }
}
