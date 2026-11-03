<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model quản lý cấu hình hệ thống (System Settings / Configuration).
 */
class CauHinhHeThong extends Model
{
    use HasFactory;

    protected $table = 'cau_hinh_he_thong';
    protected $primaryKey = 'khoa';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'khoa',
        'gia_tri',
        'kieu_du_lieu',
        'nhom',
        'tieu_de',
        'mo_ta',
    ];

    /**
     * Ép kiểu giá trị cấu hình tương ứng với kieu_du_lieu
     */
    public function getGiaTriTypedAttribute(): mixed
    {
        return match ($this->kieu_du_lieu) {
            'integer' => (int) $this->gia_tri,
            'boolean' => filter_var($this->gia_tri, FILTER_VALIDATE_BOOLEAN),
            'json'    => json_decode($this->gia_tri ?? '[]', true),
            default   => $this->gia_tri,
        };
    }

    /**
     * Lấy giá trị cấu hình theo khóa
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::find($key);
        return $setting ? $setting->gia_tri_typed : $default;
    }
}

