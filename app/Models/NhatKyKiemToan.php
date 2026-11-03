<?php

namespace App\Models;

use App\Modules\Administration\Domain\Enums\HanhDongKiemToan;
use App\Modules\Administration\Domain\Enums\LoaiDoiTuongKiemToan;
use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model đại diện cho bảng nhật ký kiểm toán nghiệp vụ Admin.
 *
 * Yêu cầu đặc tả:
 * - Append-Only & Bất biến (Immutable): Không thể chỉnh sửa hoặc xóa qua ORM hay Controller.
 * - Lưu vết đầy đủ: Người thực hiện, hành động, đối tượng bị tác động, snapshot dữ liệu cũ/mới, IP và thời điểm.
 */
class NhatKyKiemToan extends Model
{
    use HasFactory;

    protected $table = 'nhat_ky_kiem_toan';

    public $timestamps = false;

    protected $fillable = [
        'ma_nguoi_thuc_hien',
        'hanh_dong',
        'loai_doi_tuong',
        'ma_doi_tuong',
        'mo_ta',
        'du_lieu_cu',
        'du_lieu_moi',
        'dia_chi_ip',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'hanh_dong'      => HanhDongKiemToan::class,
            'loai_doi_tuong' => LoaiDoiTuongKiemToan::class,
            'du_lieu_cu'     => 'array',
            'du_lieu_moi'    => 'array',
            'created_at'     => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new DomainException('Bản ghi nhật ký kiểm toán là bất biến (immutable), không được phép chỉnh sửa.');
        });

        static::deleting(function (): never {
            throw new DomainException('Bản ghi nhật ký kiểm toán là bất biến (immutable), không được phép xóa.');
        });
    }

    public function delete(): ?bool
    {
        throw new DomainException('Bản ghi nhật ký kiểm toán là bất biến (immutable), không được phép xóa.');
    }

    public function nguoiThucHien(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'ma_nguoi_thuc_hien', 'ma_nguoi_dung');
    }
}

