<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LanNopTaiLieuThayThe extends Model
{
    use HasFactory;

    protected $table = 'lan_nop_tai_lieu_thay_the';
    protected $primaryKey = 'ma_lan_nop_thay_the';

    const CREATED_AT = 'ngay_tao';
    const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'ma_yeu_cau_chinh_sua',
        'ma_tai_lieu',
        'ma_nguoi_nop',
        'ghi_chu',
        'la_phien_ban_duoc_chap_nhan',
    ];

    protected function casts(): array
    {
        return [
            'la_phien_ban_duoc_chap_nhan' => 'boolean',
        ];
    }

    public function yeuCauChinhSua(): BelongsTo
    {
        return $this->belongsTo(YeuCauChinhSuaDongGop::class, 'ma_yeu_cau_chinh_sua', 'ma_yeu_cau_chinh_sua');
    }

    public function taiLieu(): BelongsTo
    {
        return $this->belongsTo(TaiLieu::class, 'ma_tai_lieu', 'ma_tai_lieu');
    }

    public function nguoiNop(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'ma_nguoi_nop', 'ma_nguoi_dung');
    }
}

