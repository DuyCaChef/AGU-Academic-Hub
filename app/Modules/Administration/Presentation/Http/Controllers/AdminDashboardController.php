<?php

namespace App\Modules\Administration\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Application\Queries\GetAdminDashboardOverviewQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    // Hiển thị màn hình Bảng điều khiển Quản trị hệ thống với số liệu tổng quan
    public function __invoke(Request $request, GetAdminDashboardOverviewQuery $query): Response
    {
        abort_unless($request->user()?->laQuanTriVien() || $request->user()?->laKiemDuyetVien(), 403, 'Bạn không có quyền truy cập bảng điều khiển quản trị.');

        return Inertia::render('Admin/Dashboard/Index', [
            'stats' => $query->execute(),
        ]);
    }
}

