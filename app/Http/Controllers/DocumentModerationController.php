<?php

namespace App\Http\Controllers;

use App\Models\BaoCaoViPham;
use App\Models\TaiLieu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DocumentModerationController extends Controller
{
    // Hàng đợi xem danh sách báo cáo vi phạm cần xử lý.
    public function indexReports(Request $request): Response
    {
        $reports = BaoCaoViPham::with(['taiLieu', 'nguoiBaoCao'])
            ->latest('ma_bao_cao')
            ->paginate(15);

        return Inertia::render('Admin/Reports/Index', [
            'reports' => $reports,
        ]);
    }


    // Quyết định xử lý báo cáo: Duyệt ẩn/xóa tài liệu hoặc Bác bỏ báo cáo.
    public function resolveReport(Request $request, int $id): RedirectResponse
    {
        $report = BaoCaoViPham::findOrFail($id);
        $action = $request->input('action'); // 'HIDE', 'DELETE', 'DISMISS'

        $report->update([
            'trang_thai_xu_ly' => $action ?? 'DA_XU_LY',
        ]);

        return back()->with('success', 'Đã cập nhật trạng thái xử lý báo cáo vi phạm thành công.');
    }


    // Danh sách tài liệu trong thùng rác (Soft Deleted).
    public function deletedDocuments(Request $request): Response
    {
        $deletedDocuments = TaiLieu::onlyTrashed()
            ->with(['nguoiDang', 'monHoc'])
            ->latest('deleted_at')
            ->paginate(15);

        return Inertia::render('Admin/Documents/Deleted', [
            'documents' => $deletedDocuments,
        ]);
    }

    // Khôi phục tài liệu đã xóa mềm.
    public function restoreDocument(Request $request, int $id): RedirectResponse
    {
        $document = TaiLieu::onlyTrashed()->findOrFail($id);
        $document->restore();

        return back()->with('success', "Đã khôi phục tài liệu \"{$document->tieu_de}\" thành công.");
    }
}
