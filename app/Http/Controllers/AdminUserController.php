<?php

namespace App\Http\Controllers;

use App\Enums\TrangThaiNguoiDung;
use App\Enums\VaiTroNguoiDung;
use App\Models\Khoa;
use App\Models\NguoiDung;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminUserController extends Controller
{

    // Danh sách người dùng và bộ lọc quản trị.
    public function index(Request $request): Response
    {
        if (!$request->user()->laQuanTriVien()) {
            abort(403, 'Bạn không có quyền truy cập trang quản lý người dùng.');
        }

        $query = NguoiDung::with(['khoa:ma_khoa,ten_khoa,ky_hieu_khoa', 'viDiem'])
        ->latest('ma_nguoi_dung');

        // Tìm kiếm từ khóa (Họ tên, Email, MSSV)
       if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('ho_ten', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('mssv', 'like', "%{$search}%");
            });
        }

        // Lọc theo vai trò
        if ($vaiTro = $request->input('vai_tro')) {
            $query->where('vai_tro', $vaiTro);
        }

        // Lọc theo trạng thái
        if ($trangThai = $request->input('trang_thai')) {
            $query->where('trang_thai', $trangThai);
        }

        // Lọc theo khoa
        if ($maKhoa = $request->input('ma_khoa')) {
            $query->where('ma_khoa', $maKhoa);
        }

        $users = $query->paginate(15)->withQueryString()->through(function ($u)
        {
           return [
                'ma_nguoi_dung' => $u->ma_nguoi_dung,
                'ho_ten'        => $u->ho_ten,
                'email'         => $u->email,
                'mssv'          => $u->mssv,
                'vai_tro'       => $u->vai_tro?->value,
                'ten_vai_tro'   => $u->vai_tro?->label(),
                'trang_thai'    => $u->trang_thai?->value,
                'ten_trang_thai'=> $u->trang_thai?->label(),
                'diem_uy_tin'   => $u->diem_uy_tin,
                'ngay_tao'      => $u->created_at->format('d/m/Y'),
                'khoa'          => $u->khoa ? [
                    'ma_khoa'      => $u->khoa->ma_khoa,
                    'ten_khoa'     => $u->khoa->ten_khoa,
                    'ky_hieu_khoa' => $u->khoa->ky_hieu_khoa,
                ] : null,
                'vi_diem'       => $u->viDiem ? [
                    'diem_kha_dung' => $u->viDiem->diem_kha_dung,
                    'diem_tam_giu'  => $u->viDiem->diem_tam_giu,
                ] : ['diem_kha_dung' => 0, 'diem_tam_giu' => 0],
            ];
        });

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'vai_tro', 'trang_thai', 'ma_khoa']),
            'faculties' => Khoa::select('ma_khoa', 'ten_khoa', 'ky_hieu_khoa')->get(),
        ]);
    }

    // Chuyển đổi trạng thái tài khoản giữa DANG_HOAT_DONG và TAM_KHOA.
    public function toggleStatus(Request $request, int $ma_nguoi_dung): RedirectResponse
    {
        if (!$request->user()->laQuanTriVien()) {
            abort(403, 'Bạn không có quyền thực hiện thao tác khóa/mở khóa tài khoản.');
        }

        $currentUser = $request->user();

        // Chặn Admin tự khóa tài khoản của chính mình
        if ($currentUser->ma_nguoi_dung === $ma_nguoi_dung) {
            return back()->with('error', 'Bạn không thể tự khóa tài khoản đang đăng nhập của chính mình!');
        }

        $user = NguoiDung::findOrFail($ma_nguoi_dung);

        if ($user->trang_thai === TrangThaiNguoiDung::DANG_HOAT_DONG) {
            $user->update(['trang_thai' => TrangThaiNguoiDung::TAM_KHOA]);
            $message = "Đã tạm khóa tài khoản của {$user->ho_ten}. Người dùng này sẽ không thể đăng nhập.";
            $type = 'warning';
        } else {
            $user->update(['trang_thai' => TrangThaiNguoiDung::DANG_HOAT_DONG]);
            $message = "Đã mở khóa và kích hoạt lại tài khoản của {$user->ho_ten}.";
            $type = 'success';
        }

        return back()->with($type, $message);
    }
}