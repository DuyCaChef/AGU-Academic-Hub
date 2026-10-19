<?php

namespace App\Models;

use App\Enums\LoaiGiaoDichDiem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GiaoDichDiem extends Model
{
    public $timestamps = false;
    protected $table = 'giao_dich_diem';
    protected $primaryKey = 'ma_giao_dich';

    protected $fillable = [
        'ma_vi',
        'loai_giao_dich',
        'so_diem',
        'thay_doi_kha_dung',
        'thay_doi_tam_giu',
        'loai_tham_chieu',
        'ma_tham_chieu',
        'khoa_chong_trung',
        'mo_ta',
        'thoi_gian_tao',
    ];

    protected function casts(): array
    {
        return [
            'so_diem' => 'integer',
            'thay_doi_kha_dung' => 'integer',
            'thay_doi_tam_giu' => 'integer',
            'loai_giao_dich' => LoaiGiaoDichDiem::class,
            'thoi_gian_tao' => 'datetime',
        ];
    }

    public function viDiem(): BelongsTo
    {
        return $this->belongsTo(ViDiem::class, 'ma_vi', 'ma_vi');
    }
}
