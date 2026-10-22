<?php

namespace App\Models;

use App\Enums\LoaiXacThuc;
use App\Enums\TrangThaiXacThuc;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YeuCauXacThuc extends Model
{
    use HasFactory;

    protected $table = 'yeu_cau_xac_thuc';
    protected $primaryKey = 'ma_yeu_cau_xac_thuc';

    protected $fillable = [
        'ma_nguoi_dung',
        'loai_xac_thuc',
        'mssv',
        'nam_tot_nghiep',
        'duong_dan_minh_chung',
        'loai_tep',        // MIME type của ảnh minh chứng (vd: image/jpeg)
        'loai_minh_chung',
        'trang_thai',
        'nguoi_duyet',
        'thoi_gian_duyet',
        'ly_do_tu_choi',
    ];

    protected function casts(): array
    {
        return [
            'nam_tot_nghiep' => 'integer',
            'thoi_gian_duyet' => 'datetime',
            'loai_xac_thuc' => LoaiXacThuc::class,
            'trang_thai' => TrangThaiXacThuc::class,
        ];
    }

    public function nguoiDung(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'ma_nguoi_dung', 'ma_nguoi_dung');
    }

    public function nguoiDuyet(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_duyet', 'ma_nguoi_dung');
    }
}
