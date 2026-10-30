<?php

namespace App\Http\Controllers;

use App\Models\BaoCaoViPham;
use App\Models\TaiLieu;
use App\Notifications\ViolationReportResolvedNotification;
use App\Shared\Services\DocumentReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
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

    public function reviewQueue(Request $request): Response
    {
        $documents = TaiLieu::query()
            ->with(['nguoiDang', 'monHoc'])
            ->whereIn('trang_thai', ['CHO_KIEM_TRA', 'TAM_AN_KIEM_TRA'])
            ->withCount(['baoCaoViPhams as bao_cao_hop_le_count' => fn ($query) => $query
                ->where('trang_thai_xu_ly', '!=', 'BAC_BO')
                ->whereHas('nguoiBaoCao', fn ($reporter) => $reporter
                    ->where('diem_uy_tin', '>=', config('moderation.trusted_reporter_reputation')))])
            ->orderByDesc('so_bao_cao_hop_le')
            ->latest('tu_dong_chuyen_luc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Documents/ReviewQueue', [
            'documents' => $documents,
            'trustedReputation' => config('moderation.trusted_reporter_reputation'),
            'reviewThreshold' => config('moderation.review_report_threshold'),
            'autoHideThreshold' => config('moderation.auto_hide_report_threshold'),
        ]);
    }

    public function reviewDocument(int $id): Response
    {
        $document = TaiLieu::withTrashed()
            ->with(['nguoiDang', 'monHoc'])
            ->findOrFail($id);

        $reports = BaoCaoViPham::with('nguoiBaoCao')
            ->where('ma_tai_lieu', $document->getKey())
            ->latest('ma_bao_cao')
            ->get();

        return Inertia::render('Admin/Documents/ReviewDetail', [
            'document' => $document,
            'reports' => $reports,
            'fileExists' => Storage::disk('local')->exists($document->duong_dan_tep),
        ]);
    }

    public function previewDocument(int $id)
    {
        $document = TaiLieu::withTrashed()->findOrFail($id);
        $path = $document->duong_dan_tep;

        abort_unless(Storage::disk('local')->exists($path), 404, 'Không tìm thấy tệp tài liệu.');

        return response()->file(Storage::disk('local')->path($path), [
            'Content-Disposition' => 'inline; filename="'.basename($path).'"',
        ]);
    }

    public function decideDocument(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['KEEP', 'RESTORE', 'HIDE', 'DELETE'])],
            'resolution_reason' => ['required', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($data, $id, $request): void {
            $document = TaiLieu::withTrashed()->findOrFail($id);
            $actionLabel = match ($data['action']) {
                'KEEP' => 'giữ nguyên',
                'RESTORE' => 'khôi phục',
                'HIDE' => 'ẩn',
                default => 'xóa mềm',
            };

            if (in_array($data['action'], ['KEEP', 'RESTORE'], true)) {
                $document->restore();
                $document->trang_thai = 'DA_DUYET';
            } elseif ($data['action'] === 'HIDE') {
                $document->trang_thai = 'TAM_AN_KIEM_TRA';
            } else {
                $document->delete();
            }

            $document->ly_do_trang_thai = "Admin {$actionLabel}: {$data['resolution_reason']}";
            $document->save();

            BaoCaoViPham::query()
                ->where('ma_tai_lieu', $document->getKey())
                ->where('trang_thai_xu_ly', 'CHO_XU_LY')
                ->update([
                    'trang_thai_xu_ly' => 'DA_XU_LY',
                    'ma_nguoi_xu_ly' => $request->user()->getKey(),
                    'ly_do_xu_ly' => $data['resolution_reason'],
                    'xu_ly_luc' => now(),
                ]);
        });

        return back()->with('success', 'Đã ghi nhận quyết định kiểm tra tài liệu.');
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
    public function resolveReport(Request $request, int $id, DocumentReportService $reportService): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['HIDE', 'DELETE', 'DISMISS'])],
            'resolution_reason' => ['required', 'string', 'max:2000'],
        ]);

        $result = DB::transaction(function () use ($data, $id, $request, $reportService) {
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
            $reportService->recalculate($document);

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
            ->latest('ngay_xoa_tam')
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
