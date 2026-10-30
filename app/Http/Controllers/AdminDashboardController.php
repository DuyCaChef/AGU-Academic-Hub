<?php

namespace App\Http\Controllers;

use App\Models\BaoCaoViPham;
use App\Models\NguoiDung;
use App\Models\TaiLieu;
use App\Models\YeuCauGoBoTaiLieu;
use App\Models\YeuCauXacThuc;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Admin/Dashboard/Index', [
            'stats' => [
                'pendingKyc' => YeuCauXacThuc::query()
                    ->where('trang_thai', 'CHO_DUYET')
                    ->count(),
                'pendingReports' => BaoCaoViPham::query()
                    ->where('trang_thai_xu_ly', 'CHO_XU_LY')
                    ->count(),
                'documentsToReview' => TaiLieu::query()
                    ->whereIn('trang_thai', ['CHO_KIEM_TRA', 'TAM_AN_KIEM_TRA'])
                    ->count(),
                'pendingRemovalRequests' => YeuCauGoBoTaiLieu::query()
                    ->where('trang_thai', 'CHO_XU_LY')
                    ->count(),
                'totalUsers' => NguoiDung::query()->count(),
                'totalDocuments' => TaiLieu::query()->count(),
            ],
        ]);
    }
}
