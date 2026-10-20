<?php

namespace App\Http\Controllers;

use App\Models\MonHoc;
use App\Models\TaiLieu;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        // lấy user đang login bằng cơ chế xác thực hiện có
        $user = $request->user();

        // tính toán các số liệu thống kê
        $stats = [
            //đếm tổng môn học
            'totalCourses' => MonHoc::where(
                'ma_khoa',
                $user->ma_khoa
            )->count(),
            // đếm số tài liệu của user
            'myDocuments' => TaiLieu::where(
                'ma_nguoi_dang',
                $user->getKey()
            )->count(),
            // điểm uy tín 
            'trustScore' => $user->diem_uy_tin
        ];
        // query tài liệu mới nhất 
        $recentDocument = TaiLieu::query()
            ->where('trang_thai', 'DA_DUYET')
            ->with('monHoc')
            ->latest()
            ->take(3)
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
            'stats' => $stats,
            'recentDocuments' => $recentDocument,
        ]);
    }
}
