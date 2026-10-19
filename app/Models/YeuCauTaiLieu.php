<?php

namespace App\Models;

use App\Enums\TrangThaiYeuCauTaiLieu;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class YeuCauTaiLieu extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'yeu_cau_tai_lieu';
    protected $primaryKey = 'ma_yeu_cau';

    const DELETED_AT = 'ngay_xoa_tam';

    protected $fillable = [
        'ma_mon_hoc',
        'ma_nguoi_tao',
        'tieu_de',
        'mo_ta',
        'ten_giang_vien',
        'phan_loai',
        'diem_thuong',
        'trang_thai',
        'ma_tai_lieu_dap_ung',
        'nguoi_xac_nhan',
        'thoi_gian_xac_nhan',
    ];

    protected function casts(): array
    {
        return [
            'diem_thuong' => 'integer',
            'trang_thai' => TrangThaiYeuCauTaiLieu::class,
            'thoi_gian_xac_nhan' => 'datetime',
        ];
    }

    public function monHoc(): BelongsTo
    {
        return $this->belongsTo(MonHoc::class, 'ma_mon_hoc', 'ma_mon_hoc');
    }

    public function nguoiTao(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'ma_nguoi_tao', 'ma_nguoi_dung');
    }

    public function taiLieuDapUng(): BelongsTo
    {
        return $this->belongsTo(TaiLieu::class, 'ma_tai_lieu_dap_ung', 'ma_tai_lieu');
    }

    public function nguoiXacNhan(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_xac_nhan', 'ma_nguoi_dung');
    }

    public function dapUngs(): HasMany
    {
        return $this->hasMany(DapUngYeuCauTaiLieu::class, 'ma_yeu_cau', 'ma_yeu_cau');
    }
}
