<?php

namespace App\Http\Controllers;

use App\Models\MonHoc;
use App\Models\TaiLieu;
use App\Modules\AcademicCatalog\Application\Queries\GetPublicFacultiesQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke(Request $request, GetPublicFacultiesQuery $facultiesQuery)
    {
        // Lấy thông tin người dùng đang đăng nhập
        $user = $request->user();

        // Sử dụng Query Class của Module AcademicCatalog để lấy danh sách khoa ưu tiên
        $faculties = $facultiesQuery->execute($user);

        // Tính toán các số liệu thống kê cá nhân của sinh viên / cựu sinh viên
        $stats = [
            'totalCourses' => $user->ma_khoa ? MonHoc::where('ma_khoa', $user->ma_khoa)->count() : MonHoc::count(),
            'myDocuments' => TaiLieu::where('ma_nguoi_dang', $user->getKey())->count(),
            'trustScore' => $user->diem_uy_tin,
        ];

        // Truy vấn tối đa 6 tài liệu đã kiểm duyệt mới nhất
        $recentDocument = TaiLieu::query()
            ->where('trang_thai', 'DA_DUYET')
            ->with(['monHoc:ma_mon_hoc,ma_hoc_phan,ten_mon_hoc'])
            ->latest()
            ->take(6)
            ->get([
                'ma_tai_lieu',
                'ma_mon_hoc',
                'tieu_de',
                'dinh_dang_tep',
                'phan_loai',
                'luot_tai',
                'created_at',
            ]);

        return Inertia::render('Dashboard', [
            'faculties' => $faculties,
            'stats' => $stats,
            'recentDocuments' => $recentDocument,
        ]);
    }
}
