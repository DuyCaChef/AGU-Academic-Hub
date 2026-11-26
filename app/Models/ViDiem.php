<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ViDiem extends Model
{
    use HasFactory;

    protected $table = 'vi_diem';
    protected $primaryKey = 'ma_vi';

    protected $fillable = [
        'ma_nguoi_dung',
        'diem_kha_dung',
        'diem_tam_giu',
    ];

    protected function casts(): array
    {
        return [
            'diem_kha_dung' => 'integer',
            'diem_tam_giu' => 'integer',
        ];
    }

    public function nguoiDung(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'ma_nguoi_dung', 'ma_nguoi_dung');
    }

    public function giaoDichs(): HasMany
    {
        return $this->hasMany(GiaoDichDiem::class, 'ma_vi', 'ma_vi');
    }
}
