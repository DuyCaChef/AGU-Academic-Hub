<?php

namespace App\Modules\Administration\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Application\Actions\UpdateSystemSettingAction;
use App\Modules\Administration\Application\Queries\GetSystemSettingsQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminSystemSettingController extends Controller
{
    /**
     * Màn hình danh sách và quản trị cấu hình hệ thống.
     */
    public function index(Request $request, GetSystemSettingsQuery $query): Response
    {
        abort_unless($request->user()->laQuanTriVien(), 403, 'Bạn không có quyền truy cập cấu hình hệ thống.');

        return Inertia::render('Admin/Settings/Index', [
            'settingsGrouped' => $query->execute(),
        ]);
    }

    /**
     * Cập nhật giá trị cấu hình hệ thống và ghi nhận kiểm toán.
     */
    public function update(
        Request $request,
        string $khoa,
        UpdateSystemSettingAction $action
    ): RedirectResponse {
        abort_unless($request->user()->laQuanTriVien(), 403, 'Bạn không có quyền thay đổi cấu hình hệ thống.');

        $data = $request->validate([
            'gia_tri' => ['required'],
        ]);

        $setting = $action->execute($khoa, $data['gia_tri'], $request->user());

        return back()->with('success', "Đã cập nhật cấu hình \"{$setting->tieu_de}\" thành công.");
    }
}

