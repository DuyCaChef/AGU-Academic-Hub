<?php

namespace App\Http\Controllers;

use App\Models\BaoCaoViPham;
use App\Models\TaiLieu;
use App\Notifications\ViolationReportResolvedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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
            ->when($request->filled('status'), fn ($query) => $query->where('trang_thai_xu_ly', $request->string('status')))
            ->when($request->filled('reason'), fn ($query) => $query->where('ly_do', $request->string('reason')))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('created_at', '<=', $request->date('to')))
            ->latest('ma_bao_cao')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Reports/Index', [
            'reports' => $reports,
            'filters' => $request->only(['status', 'reason', 'from', 'to']),
            'reasons' => ['TRUNG_LAP', 'SAI_NOI_DUNG', 'MA_DOC', 'BAN_QUYEN', 'KHAC'],
        ]);
    }

    public function showReport(int $id): Response
    {
        $report = BaoCaoViPham::with([
            'taiLieu' => fn ($query) => $query->withTrashed()->with('nguoiDang'),
            'nguoiBaoCao',
            'nguoiXuLy',
        ])->findOrFail($id);

        $relatedReports = BaoCaoViPham::with(['nguoiBaoCao', 'nguoiXuLy'])
            ->where('ma_tai_lieu', $report->ma_tai_lieu)
            ->where('ma_bao_cao', '!=', $report->ma_bao_cao)
            ->latest('ma_bao_cao')
            ->get();

        return Inertia::render('Admin/Reports/Show', compact('report', 'relatedReports'));
    }


    // Quyết định xử lý báo cáo: Duyệt ẩn/xóa tài liệu hoặc Bác bỏ báo cáo.
    public function resolveReport(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['HIDE', 'DELETE', 'DISMISS'])],
            'resolution_reason' => ['required', 'string', 'max:2000'],
        ]);

        $result = DB::transaction(function () use ($data, $id, $request) {
            $report = BaoCaoViPham::with(['taiLieu', 'nguoiBaoCao'])->findOrFail($id);
            $document = TaiLieu::withTrashed()->findOrFail($report->ma_tai_lieu);
            $now = now();
            $status = $data['action'] === 'DISMISS' ? 'BAC_BO' : 'DA_XU_LY';
            $actionLabel = match ($data['action']) {
                'HIDE' => 'ẩn để kiểm tra',
                'DELETE' => 'xóa mềm',
                default => 'bác bỏ',
            };

            if ($data['action'] === 'HIDE') {
                $document->update(['trang_thai' => 'TAM_AN_KIEM_TRA']);
            } elseif ($data['action'] === 'DELETE' && ! $document->trashed()) {
                $document->delete();
            }

            $reportsToResolve = $data['action'] === 'DISMISS'
                ? collect([$report])
                : BaoCaoViPham::with('nguoiBaoCao')
                    ->where('ma_tai_lieu', $report->ma_tai_lieu)
                    ->where('trang_thai_xu_ly', 'CHO_XU_LY')
                    ->get();

            foreach ($reportsToResolve as $relatedReport) {
                $relatedReport->update([
                    'trang_thai_xu_ly' => $status,
                    'ma_nguoi_xu_ly' => $request->user()->getKey(),
                    'ly_do_xu_ly' => $data['resolution_reason'],
                    'xu_ly_luc' => $now,
                ]);
            }

            $report->refresh()->load(['nguoiBaoCao']);
            $report->setRelation('taiLieu', $document);
            $recipients = collect([$document->nguoiDang, ...$reportsToResolve->pluck('nguoiBaoCao')])
                ->filter()
                ->unique('ma_nguoi_dung');

            foreach ($recipients as $recipient) {
                $recipient->notify(new ViolationReportResolvedNotification($report, $actionLabel));
            }

            return $actionLabel;
        });

        return back()->with('success', "Đã {$result} tài liệu và ghi nhận kết quả xử lý.");
    }

    public function archiveReport(int $id): RedirectResponse
    {
        $report = BaoCaoViPham::findOrFail($id);

        if (! in_array($report->trang_thai_xu_ly, ['DA_XU_LY', 'BAC_BO'], true)) {
            abort(422, 'Chỉ có thể lưu trữ báo cáo đã xử lý hoặc đã bác bỏ.');
        }

        $report->delete();

        return back()->with('success', 'Đã lưu trữ báo cáo khỏi hàng đợi.');
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
