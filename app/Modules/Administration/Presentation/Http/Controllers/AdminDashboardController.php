<?php

namespace App\Modules\Administration\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Application\Queries\GetAdminDashboardOverviewQuery;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    // Hiển thị màn hình Bảng điều khiển Quản trị hệ thống với số liệu tổng quan
    public function __invoke(GetAdminDashboardOverviewQuery $query): Response
    {
        return Inertia::render('Admin/Dashboard/Index', [
            'stats' => $query->execute(),
        ]);
    }
}

