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
        'ma_nguoi_danh_gia',
        'hoc_ky',
        'diem_do_kho',
        'diem_thuc_tien',
        'diem_khoi_luong',
        'diem_ap_luc_bai_tap',
        'noi_dung',
        'nhan_xet',
        'an_danh',
        'la_an_danh',
        'trang_thai',
    ];

    // Accessors & Mutators hỗ trợ tương thích ngược giữa các tên trường
    public function getDiemApLucBaiTapAttribute(): ?int
    {
        return $this->attributes['diem_khoi_luong'] ?? null;
    }

    public function setDiemApLucBaiTapAttribute($value): void
    {
        $this->attributes['diem_khoi_luong'] = $value;
    }

    public function getMaNguoiDanhGiaAttribute(): ?int
    {
        return $this->attributes['ma_nguoi_dung'] ?? null;
    }

    public function setMaNguoiDanhGiaAttribute($value): void
    {
        $this->attributes['ma_nguoi_dung'] = $value;
    }

    public function getNhanXetAttribute(): ?string
    {
        return $this->attributes['noi_dung'] ?? null;
    }

    public function setNhanXetAttribute($value): void
    {
        $this->attributes['noi_dung'] = $value;
    }

    public function getLaAnDanhAttribute(): ?bool
    {
        return isset($this->attributes['an_danh']) ? (bool) $this->attributes['an_danh'] : null;
    }

    public function setLaAnDanhAttribute($value): void
    {
        $this->attributes['an_danh'] = $value;
    }

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
