<?php

namespace App\Models;

use App\Enums\TrangThaiNguoiDung;
use App\Enums\CapQuanTri;
use App\Enums\VaiTroNguoiDung;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class NguoiDung extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $table = 'nguoi_dung';
    protected $primaryKey = 'ma_nguoi_dung';

    const DELETED_AT = 'ngay_xoa_tam';

    protected $fillable = [
        'ma_khoa',
        'ho_ten',
        'email',
        'mssv',
        'mat_khau',
        'vai_tro',
        'cap_quan_tri',
        'trang_thai',
        'ly_do_khoa',
        'so_lan_vi_pham',
        'muc_canh_bao',
        'diem_uy_tin',
        'anh_dai_dien',
        'xac_thuc_email_luc',
        'nam_tot_nghiep',
    ];

    protected $hidden = [
        'mat_khau',
        'remember_token',
    ];

    public function getAuthPasswordName(): string
    {
        return 'mat_khau';
    }

    public function getAuthPassword(): string
    {
        return $this->mat_khau;
    }

    public function getNameAttribute(): ?string
    {
        return $this->ho_ten;
    }

    public function getIdAttribute(): ?int
    {
        return $this->ma_nguoi_dung;
    }

    protected function casts(): array
    {
        return [
            'xac_thuc_email_luc' => 'datetime',
            'mat_khau' => 'hashed',
            'vai_tro' => VaiTroNguoiDung::class,
            'cap_quan_tri' => CapQuanTri::class,
            'trang_thai' => TrangThaiNguoiDung::class,
            'diem_uy_tin' => 'integer',
            'nam_tot_nghiep' => 'integer',
            'so_lan_vi_pham' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (NguoiDung $nguoiDung) {
            // Tự động khởi tạo ví điểm cho người dùng mới với 100 điểm ban đầu
            $nguoiDung->viDiem()->create([
                'diem_kha_dung' => 100,
                'diem_tam_giu' => 0,
            ]);
        });
    }

    // --- Relationships ---
    public function khoa(): BelongsTo
    {
        return $this->belongsTo(Khoa::class, 'ma_khoa', 'ma_khoa');
    }

    public function viDiem(): HasOne
    {
        return $this->hasOne(ViDiem::class, 'ma_nguoi_dung', 'ma_nguoi_dung');
    }

    public function danhGiaMonHocs(): HasMany
    {
        return $this->hasMany(DanhGiaMonHoc::class, 'ma_nguoi_dung', 'ma_nguoi_dung');
    }

    public function yeuCauTaiLieus(): HasMany
    {
        return $this->hasMany(YeuCauTaiLieu::class, 'ma_nguoi_tao', 'ma_nguoi_dung');
    }

    public function taiLieuDaDangs(): HasMany
    {
        return $this->hasMany(TaiLieu::class, 'ma_nguoi_dang', 'ma_nguoi_dung');
    }

    public function yeuCauXacThucs(): HasMany
    {
        return $this->hasMany(YeuCauXacThuc::class, 'ma_nguoi_dung', 'ma_nguoi_dung');
    }

    public function lichSuTrangThai(): HasMany
    {
        return $this->hasMany(LichSuTrangThaiNguoiDung::class, 'ma_nguoi_dung', 'ma_nguoi_dung');
    }

    public function lichSuPhanQuyen(): HasMany
    {
        return $this->hasMany(LichSuPhanQuyenNguoiDung::class, 'ma_nguoi_dung', 'ma_nguoi_dung');
    }

    // --- Trợ giúp phân quyền ---
    public function laQuanTriVien(): bool
    {
        return $this->vai_tro === VaiTroNguoiDung::QUAN_TRI_VIEN;
    }

    public function laSuperAdmin(): bool
    {
        return $this->laQuanTriVien() && $this->cap_quan_tri === CapQuanTri::TOI_CAO;
    }

    public function laAdminThuong(): bool
    {
        return $this->laQuanTriVien() && $this->cap_quan_tri === CapQuanTri::THUONG;
    }

    public function laKiemDuyetVien(): bool
    {
        return $this->vai_tro === VaiTroNguoiDung::KIEM_DUYET_VIEN;
    }

    public function dangHoatDong(): bool
    {
        return $this->trang_thai === TrangThaiNguoiDung::DANG_HOAT_DONG;
    }
}
