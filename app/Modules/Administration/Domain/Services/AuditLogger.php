<?php

namespace App\Modules\Administration\Domain\Services;

use App\Models\NguoiDung;
use App\Models\NhatKyKiemToan;
use App\Modules\Administration\Domain\Enums\HanhDongKiemToan;
use App\Modules\Administration\Domain\Enums\LoaiDoiTuongKiemToan;
use Illuminate\Support\Facades\Auth;

// Domain Service chuyên trách ghi nhận nhật ký kiểm toán nghiệp vụ Admin. 
// Đảm bảo mọi tác động quan trọng của quản trị viên được lưu vết bất biến, độc lập và an toàn cho việc hậu kiểm dữ liệu.
class AuditLogger
{
    // Ghi một bản ghi nhật ký kiểm toán mới.
     // @param NguoiDung|int|null $actor Người thực hiện (nếu null sẽ lấy người dùng đang đăng nhập)
     // @param HanhDongKiemToan|string $action Hành động nghiệp vụ
     // @param LoaiDoiTuongKiemToan|string $targetType Loại đối tượng bị tác động
     // @param string|int|null $targetId Mã hoặc định danh đối tượng bị tác động
     // @param string $description Mô tả chi tiết hành động bằng ngôn ngữ tự nhiên
     // @param array|null $oldData Trạng thái / dữ liệu trước khi thay đổi
     // @param array|null $newData Trạng thái / dữ liệu sau khi thay đổi
     // @param string|null $ip Địa chỉ IP (mặc định lấy từ Request hiện tại)
     // @param string|null $userAgent User Agent (mặc định lấy từ Request hiện tại)
    public function log(
        NguoiDung|int|null $actor,
        HanhDongKiemToan|string $action,
        LoaiDoiTuongKiemToan|string $targetType,
        string|int|null $targetId,
        string $description,
        ?array $oldData = null,
        ?array $newData = null,
        ?string $ip = null,
        ?string $userAgent = null
    ): NhatKyKiemToan {
        $actorId = $actor instanceof NguoiDung
            ? $actor->getKey()
            : ($actor ?? Auth::id());

        $actionEnum = is_string($action) ? HanhDongKiemToan::from($action) : $action;
        $targetTypeEnum = is_string($targetType) ? LoaiDoiTuongKiemToan::from($targetType) : $targetType;

        $clientIp = $ip ?? (app()->runningInConsole() ? '127.0.0.1' : request()?->ip());
        $clientUserAgent = $userAgent ?? (app()->runningInConsole() ? 'CLI' : request()?->userAgent());

        return NhatKyKiemToan::create([
            'ma_nguoi_thuc_hien' => $actorId,
            'hanh_dong'          => $actionEnum,
            'loai_doi_tuong'     => $targetTypeEnum,
            'ma_doi_tuong'       => $targetId !== null ? (string) $targetId : null,
            'mo_ta'              => $description,
            'du_lieu_cu'         => $oldData,
            'du_lieu_moi'        => $newData,
            'dia_chi_ip'         => $clientIp ? substr($clientIp, 0, 45) : null,
            'user_agent'         => $clientUserAgent ? substr($clientUserAgent, 0, 500) : null,
            'created_at'         => now(),
        ]);
    }
}

