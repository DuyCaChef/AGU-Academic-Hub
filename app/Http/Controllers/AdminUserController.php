<?php

namespace App\Http\Controllers;

use App\Enums\TrangThaiNguoiDung;
use App\Enums\CapQuanTri;
use App\Enums\VaiTroNguoiDung;
use App\Models\Khoa;
use App\Models\LichSuPhanQuyenNguoiDung;
use App\Models\LichSuTrangThaiNguoiDung;
use App\Models\NguoiDung;
use Illuminate\Support\Facades\DB;
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

        $users = $query->paginate(15)->withQueryString()->through(function ($u) {
            return [
                'ma_nguoi_dung' => $u->ma_nguoi_dung,
                'ho_ten' => $u->ho_ten,
                'email' => $u->email,
                'mssv' => $u->mssv,
                'vai_tro' => $u->vai_tro?->value,
                'ten_vai_tro' => $u->vai_tro?->label(),
                'cap_quan_tri' => $u->cap_quan_tri?->value,
                'ten_cap_quan_tri' => $u->cap_quan_tri?->label(),
                'trang_thai' => $u->trang_thai?->value,
                'ten_trang_thai' => $u->trang_thai?->label(),
                'ly_do_khoa' => $u->ly_do_khoa,
                'so_lan_vi_pham' => $u->so_lan_vi_pham,
                'muc_canh_bao' => $u->muc_canh_bao,
                'diem_uy_tin' => $u->diem_uy_tin,
                'ngay_tao' => $u->created_at->format('d/m/Y'),
                'khoa'          => in_array($u->vai_tro, [VaiTroNguoiDung::SINH_VIEN, VaiTroNguoiDung::CUU_SINH_VIEN], true) && $u->khoa ? [
                    'ma_khoa' => $u->khoa->ma_khoa,
                    'ten_khoa' => $u->khoa->ten_khoa,
                    'ky_hieu_khoa' => $u->khoa->ky_hieu_khoa,
                ] : null,
                'vi_diem' => $u->viDiem ? [
                    'diem_kha_dung' => $u->viDiem->diem_kha_dung,
                    'diem_tam_giu' => $u->viDiem->diem_tam_giu,
                ] : ['diem_kha_dung' => 0, 'diem_tam_giu' => 0],
            ];
        });

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'vai_tro', 'trang_thai', 'ma_khoa']),
            'faculties' => Khoa::select('ma_khoa', 'ten_khoa', 'ky_hieu_khoa')->get(),
        ]);
    }

    // Xem hồ sơ chi tiết của một tài khoản và toàn bộ lịch sử can thiệp.
    public function show(Request $request, int $ma_nguoi_dung): Response
    {
        $this->authorizeAdmin($request);
        $user = NguoiDung::with(['khoa', 'viDiem'])->findOrFail($ma_nguoi_dung);

        return Inertia::render('Admin/Users/Show', [
            'user' => $this->serializeUser($user),
            'permissions' => [
                'can_manage_target' => $this->canManageTarget($request->user(), $user),
                'can_manage_admin' => $request->user()->laSuperAdmin(),
            ],
            'faculties' => Khoa::select('ma_khoa', 'ten_khoa', 'ky_hieu_khoa')->get(),
            'statusHistory' => LichSuTrangThaiNguoiDung::with('nguoiThucHien:ma_nguoi_dung,ho_ten')
                ->where('ma_nguoi_dung', $user->ma_nguoi_dung)->latest()->get()
                ->map(fn(LichSuTrangThaiNguoiDung $item) => [
                    'trang_thai_cu' => $item->trang_thai_cu,
                    'trang_thai_moi' => $item->trang_thai_moi,
                    'hanh_dong' => $item->hanh_dong,
                    'ly_do' => $item->ly_do,
                    'nguoi_thuc_hien' => $item->nguoiThucHien?->ho_ten,
                    'thoi_diem' => $item->created_at->format('d/m/Y H:i'),
                ]),
            'roleHistory' => LichSuPhanQuyenNguoiDung::with('nguoiThucHien:ma_nguoi_dung,ho_ten')
                ->where('ma_nguoi_dung', $user->ma_nguoi_dung)->latest()->get()
                ->map(fn(LichSuPhanQuyenNguoiDung $item) => [
                    'vai_tro_cu' => $item->vai_tro_cu,
                    'vai_tro_moi' => $item->vai_tro_moi,
                            'cap_quan_tri_cu' => $item->cap_quan_tri_cu,
                            'cap_quan_tri_moi' => $item->cap_quan_tri_moi,
                    'ma_khoa_cu' => $item->ma_khoa_cu,
                    'ma_khoa_moi' => $item->ma_khoa_moi,
                    'ly_do' => $item->ly_do,
                    'nguoi_thuc_hien' => $item->nguoiThucHien?->ho_ten,
                    'thoi_diem' => $item->created_at->format('d/m/Y H:i'),
                ]),
        ]);
    }

    // Cập nhật vai trò phân quyền (RBAC) và gán Khoa cho người dùng.
    public function updatePermissions(Request $request, int $ma_nguoi_dung): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $actor = $request->user();
        $user = NguoiDung::findOrFail($ma_nguoi_dung);
        $data = $request->validate([
            'vai_tro' => ['required', 'in:SINH_VIEN,CUU_SINH_VIEN,KIEM_DUYET_VIEN,QUAN_TRI_VIEN'],
            'ma_khoa' => ['nullable', 'integer', 'exists:khoa,ma_khoa'],
            'cap_quan_tri' => ['nullable', 'in:THUONG,TOI_CAO'],
            'ly_do' => ['nullable', 'string', 'max:1000'],
        ]);
        if (in_array($data['vai_tro'], ['KIEM_DUYET_VIEN', 'QUAN_TRI_VIEN'], true) && filled($data['ma_khoa'] ?? null)) {
            return back()
                ->withErrors(['ma_khoa' => 'Tài khoản Kiểm duyệt viên và Quản trị viên không được gán Khoa.'])
                ->withInput();
        }

        if ($actor->is($user)) {
            return back()->with('error', 'Bạn không thể tự thay đổi vai trò hoặc Khoa của tài khoản mình.');
        }

        $role = VaiTroNguoiDung::from($data['vai_tro']);
        if (!$this->canManageTarget($actor, $user) || (!$actor->laSuperAdmin() && in_array($role, [VaiTroNguoiDung::KIEM_DUYET_VIEN, VaiTroNguoiDung::QUAN_TRI_VIEN], true))) {
            return back()->with('error', 'Admin thường không được thay đổi quyền quản trị của tài khoản này.');
        }
        if ($role === VaiTroNguoiDung::QUAN_TRI_VIEN && !$actor->laSuperAdmin()) {
            return back()->with('error', 'Chỉ Super Admin mới được cấp quyền Admin.');
        }
        if ($user->laSuperAdmin() && ($role !== VaiTroNguoiDung::QUAN_TRI_VIEN || ($data['cap_quan_tri'] ?? null) !== CapQuanTri::TOI_CAO->value)
            && NguoiDung::where('vai_tro', VaiTroNguoiDung::QUAN_TRI_VIEN)->where('cap_quan_tri', CapQuanTri::TOI_CAO)->count() <= 1) {
            return back()->with('error', 'Không thể hạ cấp Super Admin cuối cùng.');
        }
        if (in_array($role, [VaiTroNguoiDung::KIEM_DUYET_VIEN, VaiTroNguoiDung::QUAN_TRI_VIEN], true)) {
            $data['ma_khoa'] = null;
        }
        $adminLevel = $role === VaiTroNguoiDung::QUAN_TRI_VIEN
            ? CapQuanTri::from($data['cap_quan_tri'] ?? CapQuanTri::THUONG->value)
            : null;
        if ($user->vai_tro?->value === $role->value && (int) $user->ma_khoa === (int) ($data['ma_khoa'] ?? 0)) {
            if ($user->cap_quan_tri === $adminLevel) {
                return back()->with('info', 'Thông tin phân quyền không thay đổi.');
            }
        }

        DB::transaction(function () use ($user, $actor, $data, $role, $adminLevel): void {
            LichSuPhanQuyenNguoiDung::create([
                'ma_nguoi_dung' => $user->ma_nguoi_dung,
                'ma_nguoi_thuc_hien' => $actor->ma_nguoi_dung,
                'vai_tro_cu' => $user->vai_tro?->value,
                'vai_tro_moi' => $role->value,
                'cap_quan_tri_cu' => $user->cap_quan_tri?->value,
                'cap_quan_tri_moi' => $adminLevel?->value,
                'ma_khoa_cu' => $user->ma_khoa,
                'ma_khoa_moi' => $data['ma_khoa'] ?? null,
                'ly_do' => $data['ly_do'] ?? null,
            ]);
            $user->update([
                'vai_tro' => $role,
                'cap_quan_tri' => $adminLevel,
                'ma_khoa' => $data['ma_khoa'] ?? null,
            ]);
        });

        return back()->with('success', "Đã cập nhật vai trò và Khoa của {$user->ho_ten}.");
    }


    // Đảo chiều trạng thái hoạt động của tài khoản (Khóa hoặc Mở khóa thủ công).
    public function toggleStatus(Request $request, int $ma_nguoi_dung): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $currentUser = $request->user();
        $user = NguoiDung::findOrFail($ma_nguoi_dung);
        if ($currentUser->is($user)) {
            return back()->with('error', 'Bạn không thể tự khóa hoặc mở khóa tài khoản của chính mình.');
        }
        if (!$this->canManageTarget($currentUser, $user)) {
            return back()->with('error', 'Admin thường không được khóa hoặc mở khóa tài khoản quản trị.');
        }

        $data = $request->validate(['ly_do' => ['nullable', 'string', 'max:1000']]);
        $newStatus = $user->trang_thai === TrangThaiNguoiDung::TAM_KHOA
            ? TrangThaiNguoiDung::DANG_HOAT_DONG
            : TrangThaiNguoiDung::TAM_KHOA;
        $action = $newStatus === TrangThaiNguoiDung::TAM_KHOA ? 'KHOA' : 'MO_KHOA';
        $reason = $data['ly_do'] ?? ($newStatus === TrangThaiNguoiDung::TAM_KHOA ? 'Quản trị viên khóa tài khoản.' : null);

        DB::transaction(function () use ($user, $currentUser, $newStatus, $action, $reason): void {
            LichSuTrangThaiNguoiDung::create([
                'ma_nguoi_dung' => $user->ma_nguoi_dung,
                'ma_nguoi_thuc_hien' => $currentUser->ma_nguoi_dung,
                'trang_thai_cu' => $user->trang_thai?->value,
                'trang_thai_moi' => $newStatus->value,
                'hanh_dong' => $action,
                'ly_do' => $reason,
            ]);
            $user->update([
                'trang_thai' => $newStatus,
                'ly_do_khoa' => $newStatus === TrangThaiNguoiDung::TAM_KHOA ? $reason : null,
            ]);
        });

        return back()->with(
            $newStatus === TrangThaiNguoiDung::TAM_KHOA ? 'warning' : 'success',
            $newStatus === TrangThaiNguoiDung::TAM_KHOA
            ? "Đã tạm khóa tài khoản của {$user->ho_ten}."
            : "Đã mở khóa và kích hoạt lại tài khoản của {$user->ho_ten}."
        );
    }

    // Ghi nhận vi phạm của người dùng và tự động nâng cấp mức cảnh báo hoặc khóa tài khoản nếu vi phạm lặp lại.
    public function recordViolation(Request $request, int $ma_nguoi_dung): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $user = NguoiDung::findOrFail($ma_nguoi_dung);
        if ($request->user()->is($user)) {
            return back()->with('error', 'Bạn không thể tự ghi nhận vi phạm cho tài khoản của mình.');
        }
        if (!$this->canManageTarget($request->user(), $user)) {
            return back()->with('error', 'Admin thường không được xử lý vi phạm của tài khoản quản trị.');
        }
        $data = $request->validate(['ly_do' => ['required', 'string', 'max:1000']]);
        $count = $user->so_lan_vi_pham + 1;
        $level = $count === 1 ? 'CANH_BAO' : 'TAM_KHOA';

        DB::transaction(function () use ($user, $request, $data, $count, $level): void {
            $oldStatus = $user->trang_thai?->value;
            $newStatus = $level === 'TAM_KHOA' ? TrangThaiNguoiDung::TAM_KHOA : $user->trang_thai;
            $user->update([
                'so_lan_vi_pham' => $count,
                'muc_canh_bao' => $level,
                'trang_thai' => $newStatus,
                'ly_do_khoa' => $newStatus === TrangThaiNguoiDung::TAM_KHOA ? $data['ly_do'] : $user->ly_do_khoa,
            ]);
            if ($newStatus !== $user->trang_thai || $newStatus === TrangThaiNguoiDung::TAM_KHOA) {
                LichSuTrangThaiNguoiDung::create([
                    'ma_nguoi_dung' => $user->ma_nguoi_dung,
                    'ma_nguoi_thuc_hien' => $request->user()->ma_nguoi_dung,
                    'trang_thai_cu' => $oldStatus,
                    'trang_thai_moi' => $newStatus->value,
                    'hanh_dong' => $level,
                    'ly_do' => $data['ly_do'],
                ]);
            }
        });

        return back()->with(
            $level === 'TAM_KHOA' ? 'warning' : 'success',
            $level === 'TAM_KHOA' ? 'Tài khoản đã bị tạm khóa do vi phạm lặp lại.' : 'Đã ghi nhận cảnh báo vi phạm.'
        );
    }

    // Kiểm tra quyền quản trị viên
    private function authorizeAdmin(Request $request): void
    {
        if (!$request->user()?->laQuanTriVien()) {
            abort(403, 'Bạn không có quyền quản trị tài khoản.');
        }
    }

    private function canManageTarget(NguoiDung $actor, NguoiDung $target): bool
    {
        if ($actor->is($target)) {
            return false;
        }

        if ($target->laQuanTriVien() || $target->laKiemDuyetVien()) {
            return $actor->laSuperAdmin();
        }

        return true;
    }


    private function serializeUser(NguoiDung $user): array
    {
        return [
            'ma_nguoi_dung' => $user->ma_nguoi_dung,
            'ho_ten' => $user->ho_ten,
            'email' => $user->email,
            'mssv' => $user->mssv,
            'vai_tro' => $user->vai_tro?->value,
            'ten_vai_tro' => $user->vai_tro?->label(),
            'cap_quan_tri' => $user->cap_quan_tri?->value,
            'ten_cap_quan_tri' => $user->cap_quan_tri?->label(),
            'trang_thai' => $user->trang_thai?->value,
            'ten_trang_thai' => $user->trang_thai?->label(),
            'ly_do_khoa' => $user->ly_do_khoa,
            'so_lan_vi_pham' => $user->so_lan_vi_pham,
            'muc_canh_bao' => $user->muc_canh_bao,
            'diem_uy_tin' => $user->diem_uy_tin,
            'nam_tot_nghiep' => $user->nam_tot_nghiep,
            'ngay_tao' => $user->created_at?->format('d/m/Y H:i'),
            'khoa' => in_array($user->vai_tro, [VaiTroNguoiDung::SINH_VIEN, VaiTroNguoiDung::CUU_SINH_VIEN], true) && $user->khoa
                ? ['ma_khoa' => $user->khoa->ma_khoa, 'ten_khoa' => $user->khoa->ten_khoa]
                : null,
            'vi_diem' => $user->viDiem ? ['diem_kha_dung' => $user->viDiem->diem_kha_dung, 'diem_tam_giu' => $user->viDiem->diem_tam_giu] : null,
        ];
    }
}