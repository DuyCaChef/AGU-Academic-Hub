<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaiLieu extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tai_lieu';
    protected $primaryKey = 'ma_tai_lieu';

    const DELETED_AT = 'ngay_xoa_tam';

    public function monHoc(): BelongsTo
    {
        return $this->belongsTo(MonHoc::class, 'ma_mon_hoc', 'ma_mon_hoc');
    }
}