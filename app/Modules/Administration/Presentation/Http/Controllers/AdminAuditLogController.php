<?php

namespace App\Modules\Administration\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Application\Queries\GetAuditLogDetailQuery;
use App\Modules\Administration\Application\Queries\GetAuditLogsQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminAuditLogController extends Controller
{
    /**
     * Hiển thị bảng điều khiển và danh sách nhật ký kiểm toán nghiệp vụ.
     */
    public function index(Request $request, GetAuditLogsQuery $query): Response
    {
        abort_unless($request->user()->laQuanTriVien(), 403, 'Bạn không có quyền truy cập nhật ký kiểm toán hệ thống.');

        $data = $query->execute($request->only([
            'search',
            'hanh_dong',
            'loai_doi_tuong',
            'ma_nguoi_thuc_hien',
            'tu_ngay',
            'den_ngay',
        ]));

        return Inertia::render('Admin/AuditLogs/Index', $data);
    }

    /**
     * Lấy dữ liệu chi tiết một bản ghi kiểm toán kèm Diff JSON.
     */
    public function show(Request $request, int $id, GetAuditLogDetailQuery $query): JsonResponse
    {
        abort_unless($request->user()->laQuanTriVien(), 403, 'Bạn không có quyền xem chi tiết kiểm toán.');

        $data = $query->execute($id);

        if (!$data) {
            return response()->json(['message' => 'Không tìm thấy bản ghi kiểm toán.'], 404);
        }

        return response()->json($data);
    }
}

