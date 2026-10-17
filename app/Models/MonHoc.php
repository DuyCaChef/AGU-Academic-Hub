<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MonHoc extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mon_hoc';
    protected $primaryKey = 'ma_mon_hoc';

    protected $fillable = [
        'ma_khoa',
        'ma_hoc_phan',
        'ten_mon_hoc',
        'so_tin_chi',
        'mo_ta',
    ];

    const DELETED_AT = 'ngay_xoa_tam';

    public function khoa(): BelongsTo
    {
        return $this->belongsTo(Khoa::class, 'ma_khoa', 'ma_khoa');
    }
}