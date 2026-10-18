<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Khoa extends Model
{
    use HasFactory, SoftDeletes;

    // Chỉ định chính xác tên bảng trong CSDL
    protected $table = 'khoa';

    // Chỉ định khóa chính (thay vì mặc định là 'id')
    protected $primaryKey = 'ma_khoa';

    protected $fillable = [
        'ky_hieu_khoa',
        'ten_khoa',
    ];

    const DELETED_AT = 'ngay_xoa_tam';

    /**
     * Mối quan hệ: Một Khoa có nhiều Môn học
     */
    public function monHocs(): HasMany
    {
        return $this->hasMany(MonHoc::class, 'ma_khoa', 'ma_khoa');
    }
}