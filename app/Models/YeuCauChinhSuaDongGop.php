<?php

namespace App\Models;

use App\Enums\TrangThaiYeuCauChinhSua;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class YeuCauChinhSuaDongGop extends Model
{
    use HasFactory;

    protected $table = 'yeu_cau_chinh_sua_dong_gop';
    protected $primaryKey = 'ma_yeu_cau_chinh_sua';

    const CREATED_AT = 'ngay_tao';
    const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'ma_yeu_cau',
        'ma_dap_ung',
        'ma_nguoi_yeu_cau',
        'ma_nguoi_dong_gop',
        'ly_do_chinh_sua',
        'ly_do_tu_choi',
        'trang_thai',
        'thoi_gian_phan_hoi',
        'thoi_gian_hoan_tat',
    ];

    protected function casts(): array
    {
        return [
            'trang_thai'         => TrangThaiYeuCauChinhSua::class,
            'thoi_gian_phan_hoi' => 'datetime',
            'thoi_gian_hoan_tat' => 'datetime',
        ];
    }

    public function yeuCau(): BelongsTo
    {
        return $this->belongsTo(YeuCauTaiLieu::class, 'ma_yeu_cau', 'ma_yeu_cau');
    }

    public function dapUng(): BelongsTo
    {
        return $this->belongsTo(DapUngYeuCauTaiLieu::class, 'ma_dap_ung', 'ma_dap_ung');
    }

    public function nguoiYeuCau(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'ma_nguoi_yeu_cau', 'ma_nguoi_dung');
    }

    public function nguoiDongGop(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'ma_nguoi_dong_gop', 'ma_nguoi_dung');
    }

    public function lanNopThayThes(): HasMany
    {
        return $this->hasMany(LanNopTaiLieuThayThe::class, 'ma_yeu_cau_chinh_sua', 'ma_yeu_cau_chinh_sua');
    }
}

