<?php

namespace App\Modules\Administration\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Application\Actions\PurgeExpiredTrashAction;
use App\Modules\Administration\Application\Queries\GetStorageCourseStatsQuery;
use App\Modules\Administration\Application\Queries\GetStorageOverviewQuery;
use App\Modules\Administration\Application\Queries\GetStorageUploaderStatsQuery;
use App\Modules\Administration\Application\Queries\GetUnusuallyLargeFilesQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminStorageController extends Controller
{
    // Màn hình Bảng điều khiển Theo dõi & Quản lý Dung lượng Lưu trữ
    public function index(
        Request $request,
        GetStorageOverviewQuery $overviewQuery,
        GetStorageCourseStatsQuery $courseQuery,
        GetStorageUploaderStatsQuery $uploaderQuery,
        GetUnusuallyLargeFilesQuery $largeFilesQuery
    ): Response {
        abort_unless($request->user()->laQuanTriVien(), 403, 'Bạn không có thẩm quyền truy cập trang quản lý lưu trữ.');

        $forceRefresh = $request->boolean('refresh');
        $overview = $overviewQuery->execute($forceRefresh);

        // Ngưỡng lọc file lớn bất thường (MB sang KB)
        $thresholdMbInput = $request->input('large_file_threshold_mb');
        $thresholdKb = $thresholdMbInput !== null && is_numeric($thresholdMbInput) && (int) $thresholdMbInput > 0
            ? (int) $thresholdMbInput * 1024
            : null;

        $courseSearch = $request->input('course_search');
        $uploaderSearch = $request->input('uploader_search');

        return Inertia::render('Admin/Storage/Index', [
            'totalStats' => $overview['totalStats'],
            'facultyStats' => $overview['facultyStats'],
            'courseStats' => $courseQuery->execute(20, $courseSearch),
            'typeStats' => $overview['typeStats'],
            'uploaderStats' => $uploaderQuery->execute(20, $uploaderSearch),
            'trashStats' => $overview['trashStats'],
            'kycStats' => $overview['kycStats'],
            'largeFiles' => $largeFilesQuery->execute($thresholdKb, 50),
            'duplicates' => $overview['duplicates'],
            'reclaimable' => $overview['reclaimable'],
            'alert' => $overview['alert'],
            'growth' => $overview['growth'],
            'cachedAt' => $overview['cachedAt'] ?? null,
            'filters' => [
                'course_search' => $courseSearch ?? '',
                'uploader_search' => $uploaderSearch ?? '',
                'large_file_threshold_mb' => $thresholdMbInput ? (int) $thresholdMbInput : (int) (config('storage.large_file_threshold_kb', 20480) / 1024),
            ],
        ]);
    }

    // Thực thi tác vụ dọn dẹp và xóa vĩnh viễn các tài liệu trong thùng rác đã quá hạn lưu trữ
    public function purgeExpiredTrash(Request $request, PurgeExpiredTrashAction $action): RedirectResponse
    {
        abort_unless($request->user()->laQuanTriVien(), 403, 'Chỉ Quản trị viên mới có quyền dọn dẹp vĩnh viễn thùng rác.');

        $result = $action->execute($request->user());

        if ($result['so_tai_lieu_da_xoa'] === 0) {
            return back()->with('info', 'Không có tài liệu nào trong thùng rác đã hết hạn lưu trữ để xóa.');
        }

        return back()->with(
            'success',
            "Đã xóa vĩnh viễn {$result['so_tai_lieu_da_xoa']} tệp quá hạn, giải phóng {$result['dung_luong_giai_phong_dinh_dang']} dung lượng lưu trữ."
        );
    }
}

