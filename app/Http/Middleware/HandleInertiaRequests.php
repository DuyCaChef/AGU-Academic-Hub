<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware {
    /**
     * Root view Blade chứa thẻ #app và nạp Vite/React.
     *
     * @var string
     */

    // Chỉ định Blade gốc làm khung chứa toàn bộ ứng dụng SPA
    protected $rootView = 'app';

    // Kiểm soát phiên bản asset bundle của ứng dụng
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    // Định nghĩa các dữ liệu được chia sẻ toàn cục xuống React Components.
    public function share(Request $request): array
    {
        $user = $request->user();

        if ($user) {
            $user->loadMissing(['khoa', 'viDiem']);
        }

        return [
            ...parent::share($request),

            // Dữ liệu phiên đăng nhập & Phân quyền người dùng (auth.user & auth.vai_tro)
            'auth' => [
                'user' => $user ? [
                    // Định danh & Thông tin cá nhân cơ bản 
                    'ma_nguoi_dung' => $user->ma_nguoi_dung,
                    'id' =>   $user->id,
                    'ho_ten' => $user->ho_ten,
                    'email' => $user->email,
                    'mssv' => $user->mssv,
                    'anh_dai_dien' => $user->anh_dai_dien,
                    'diem_uy_tin' => $user->diem_uy_tin,
                    'nam_tot_nghiep' => $user->nam_tot_nghiep,

                    // Vai trò & Trạng thái tài khoản (Lấy từ Backed Enum)
                    'vai_tro' => $user->vai_tro?->value, // Trả về 'SINH_VIEN', 'CUU_SINH_VIEN', 'KIEM_DUYET_VIEN', 'QUAN_TRI_VIEN'
                    'ten_vai_tro' => $user->vai_tro?->label(), // Trả về "Sinh viên", "Cựu sinh viên", "Kiểm duyệt viên", "Quản trị viên"
                    'cap_quan_tri' => $user->cap_quan_tri?->value,
                    'ten_cap_quan_tri' => $user->cap_quan_tri?->label(),
                    'trang_thai' => $user->trang_thai?->value, 
                    'ten_trang_thai' => $user->trang_thai?->label(),

                    // Cờ phân quyền nhanh (Helper Flags)
                    'la_quan_tri' => $user->laQuanTriVien(),
                    'la_super_admin' => $user->laSuperAdmin(),
                    'la_kiem_duyet' => $user->laKiemDuyetVien(),
                    'dang_hoat_dong' => $user->dangHoatDong(),

                    // Thông tin Khoa đào tạo
                    'khoa' => $user->khoa ? [
                        'ma_khoa'      => $user->khoa->ma_khoa,
                        'ky_hieu_khoa' => $user->khoa->ky_hieu_khoa,
                        'ten_khoa'     => $user->khoa->ten_khoa,
                    ] : null,

                    // Ví điểm cá nhân
                    'vi_diem' => $user->viDiem ? [
                        'diem_kha_dung' => $user->viDiem->diem_kha_dung,
                        'diem_tam_giu'  => $user->viDiem->diem_tam_giu,
                    ] : [
                        'diem_kha_dung' => 0,
                        'diem_tam_giu'  => 0,
                    ],
                ] : null,

                // Chia sẻ vai trò toàn cục trực tiếp trong auth
                'vai_tro' => $user?->vai_tro?->value,
            ],

            // Thông báo Flash Session (Toast notifications)
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info'    => fn () => $request->session()->get('info'),
            ],

            // Ziggy route helper cho React
            // Định tuyến thông minh phía Client, cung cấp toàn bộ cây định tuyến từ BE xuống FE
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
        ];
    }
}