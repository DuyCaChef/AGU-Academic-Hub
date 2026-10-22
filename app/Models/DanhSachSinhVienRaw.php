<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Model đại diện cho bảng dữ liệu thô sinh viên đang học.
 *
 * Dữ liệu được Admin/Nhà trường import vào. Sinh viên không tự đăng ký vào bảng này.
 * Bảng này chỉ dùng để xác minh danh tính lần đăng nhập đầu tiên.
 */
class DanhSachSinhVienRaw extends Model
{
    use HasFactory;

    protected $table = 'danh_sach_sinh_vien_raw';
    protected $primaryKey = 'ma_ban_ghi';

    protected $fillable = [
        'mssv',
        'ho_ten',
        'email_sv',
        'mat_khau_mac_dinh',
        'ma_khoa',
        'da_kich_hoat',
        'kich_hoat_luc',
    ];

    protected $hidden = [
        'mat_khau_mac_dinh',
    ];

    protected function casts(): array
    {
        return [
            'da_kich_hoat'  => 'boolean',
            'kich_hoat_luc' => 'datetime',
            'mat_khau_mac_dinh' => 'hashed',
        ];
    }

    /**
     * Tìm bản ghi theo MSSV (không phân biệt hoa thường).
     */
    public static function findByMssv(string $mssv): ?self
    {
        return static::where('mssv', strtoupper(trim($mssv)))->first();
    }

    /**
     * Kiểm tra sinh viên này đã kích hoạt tài khoản chưa.
     */
    public function daKichHoat(): bool
    {
        return $this->da_kich_hoat === true;
    }

    /**
     * Đánh dấu đã kích hoạt và ghi thời gian.
     */
    public function markAsActivated(): bool
    {
        return $this->update([
            'da_kich_hoat' => true,
            'kich_hoat_luc' => now(),
        ]);
    }
}
