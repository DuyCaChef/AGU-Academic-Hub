<?php

namespace App\Http\Controllers;

use App\Models\BaoCaoViPham;
use App\Models\LichSuThungRac;
use App\Models\TaiLieu;
use App\Notifications\ViolationReportResolvedNotification;
use App\Shared\Services\DocumentReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class DocumentModerationController extends Controller
{
    // Hàng đợi xem danh sách báo cáo vi phạm cần xử lý.
    public function indexReports(Request $request): Response
    {
        $reports = BaoCaoViPham::with(['taiLieu', 'nguoiBaoCao'])
            ->when($request->filled('status'), fn($query) => $query->where('trang_thai_xu_ly', $request->string('status')))
            ->when($request->filled('reason'), fn($query) => $query->where('ly_do', $request->string('reason')))
            ->when($request->filled('from'), fn($query) => $query->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn($query) => $query->whereDate('created_at', '<=', $request->date('to')))
            ->latest('ma_bao_cao')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Reports/Index', [
            'reports' => $reports,
            'filters' => $request->only(['status', 'reason', 'from', 'to']),
            'reasons' => ['TRUNG_LAP', 'SAI_NOI_DUNG', 'MA_DOC', 'BAN_QUYEN', 'KHAC'],
        ]);
    }

    // Hàng đợi kiểm tra riêng biệt dành cho các tài liệu bị gắn cờ nguy cơ cao.
    public function reviewQueue(Request $request): Response
    {
        $documents = TaiLieu::query()
            ->with(['nguoiDang', 'monHoc'])
            ->whereIn('trang_thai', ['CHO_KIEM_TRA', 'TAM_AN_KIEM_TRA'])
            ->withCount([
                'baoCaoViPhams as bao_cao_hop_le_count' => fn($query) => $query
                    ->where('trang_thai_xu_ly', '!=', 'BAC_BO')
                    ->whereHas('nguoiBaoCao', fn($reporter) => $reporter
                        ->where('diem_uy_tin', '>=', config('moderation.trusted_reporter_reputation')))
            ])
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

    // Chi tiết thẩm định tài liệu trong hàng đợi kiểm tra. Cho phép nạp cả tài liệu đã xóa mềm (trashed).
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

    // Xem trước nội dung tệp trực tuyến từ ổ đĩa nội bộ (Protected Inline Stream).
    public function previewDocument(int $id)
    {
        $document = TaiLieu::withTrashed()->findOrFail($id);
        $path = $document->duong_dan_tep;

        abort_unless(Storage::disk('local')->exists($path), 404, 'Không tìm thấy tệp tài liệu.');

        return response()->file(Storage::disk('local')->path($path), [
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
        ]);
    }


    // Quyết định xóa tài liệu, khôi phục, hoặc giữ nguyên tài liệu trong thùng rác.
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
                if ($data['action'] === 'RESTORE') {
                    LichSuThungRac::create([
                        'ma_tai_lieu' => $document->getKey(),
                        'ma_nguoi_thuc_hien' => $request->user()->getKey(),
                        'hanh_dong' => 'RESTORE',
                        'nguon' => $request->user()->laQuanTriVien() ? 'ADMIN' : 'MODERATOR',
                        'ly_do' => $data['resolution_reason'],
                    ]);
                }
            } elseif ($data['action'] === 'HIDE') {
                $document->trang_thai = 'TAM_AN_KIEM_TRA';
            } else {
                $this->softDeleteDocument($document, $request->user(), $data['resolution_reason']);
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

    // Xem chi tiết một bản ghi báo cáo cụ thể và danh sách báo cáo liên quan cùng tài liệu.
    public function showReport(int $id): Response
    {
        $report = BaoCaoViPham::with([
            'taiLieu' => fn($query) => $query->withTrashed()->with('nguoiDang'),
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
            } elseif ($data['action'] === 'DELETE' && !$document->trashed()) {
                $this->softDeleteDocument($document, $request->user(), $data['resolution_reason']);
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

    // Xóa mềm (Archive) bản ghi báo cáo để dọn sạch hàng đợi.
    public function archiveReport(int $id): RedirectResponse
    {
        $report = BaoCaoViPham::findOrFail($id);

        if (!in_array($report->trang_thai_xu_ly, ['DA_XU_LY', 'BAC_BO'], true)) {
            abort(422, 'Chỉ có thể lưu trữ báo cáo đã xử lý hoặc đã bác bỏ.');
        }

        $report->delete();

        return back()->with('success', 'Đã lưu trữ báo cáo khỏi hàng đợi.');
    }

    // Danh sách tài liệu trong thùng rác (Soft Deleted).
    public function deletedDocuments(Request $request): Response
    {
        $deletedDocuments = TaiLieu::onlyTrashed()
            ->with(['nguoiDang', 'nguoiXoa', 'monHoc', 'baoCaoViPhams'])
            ->when($request->filled('from'), fn($query) => $query->whereDate('ngay_xoa_tam', '>=', $request->date('from')))
            ->when($request->filled('to'), fn($query) => $query->whereDate('ngay_xoa_tam', '<=', $request->date('to')))
            ->when($request->filled('deleted_by'), fn($query) => $query->where('ma_nguoi_xoa', $request->integer('deleted_by')))
            ->when($request->filled('source'), fn($query) => $query->where('nguon_xoa', $request->string('source')))
            ->when($request->filled('status'), fn($query) => $query->where('trang_thai', $request->string('status')))
            ->latest('ngay_xoa_tam')
            ->paginate(15)
            ->withQueryString();
        $deletedDocuments->getCollection()->each(function (TaiLieu $document): void {
            $eligibleAt = $document->han_luu_thung_rac
                ?? $document->ngay_xoa_tam?->copy()->addDays(config('documents.trash_admin_delete_after_days'));
            $document->setAttribute('co_the_xoa_vinh_vien', $eligibleAt?->isPast() ?? false);
            $document->setAttribute('thoi_diem_xoa_vinh_vien_thu_cong', $eligibleAt);
        });

        return Inertia::render('Admin/Documents/Deleted', [
            'documents' => $deletedDocuments,
            'filters' => $request->only(['from', 'to', 'deleted_by', 'source', 'status']),
            'actors' => \App\Models\NguoiDung::query()
                ->whereIn('vai_tro', ['SINH_VIEN', 'CUU_SINH_VIEN', 'KIEM_DUYET_VIEN', 'QUAN_TRI_VIEN'])
                ->orderBy('ho_ten')
                ->get(['ma_nguoi_dung', 'ho_ten', 'vai_tro']),
            'sources' => ['TAC_GIA', 'MODERATOR', 'ADMIN', 'SYSTEM'],
            'statuses' => ['CHO_DUYET', 'DA_DUYET', 'CHO_KIEM_TRA', 'TAM_AN_KIEM_TRA', 'TU_CHOI'],
            'canPermanentlyDelete' => $request->user()->laQuanTriVien(),
            'adminDeleteAfterDays' => config('documents.trash_admin_delete_after_days'),
            'autoPurgeAfterDays' => config('documents.trash_auto_purge_after_days'),
        ]);
    }

    // Khôi phục tài liệu đã xóa mềm.
    public function restoreDocument(Request $request, int $id): RedirectResponse
    {
        $document = DB::transaction(function () use ($request, $id): TaiLieu {
            $document = TaiLieu::onlyTrashed()
                ->with(['taiLieuGoc', 'phienBans'])
                ->lockForUpdate()
                ->findOrFail($id);

            if ($document->ma_tai_lieu_goc && !$document->taiLieuGoc) {
                abort(409, 'Không thể khôi phục: phiên bản gốc không còn tồn tại.');
            }

            $hasActiveViolations = BaoCaoViPham::query()
                ->where('ma_tai_lieu', $document->getKey())
                ->where('trang_thai_xu_ly', 'CHO_XU_LY')
                ->exists();
            if ($hasActiveViolations && !$request->boolean('confirm_violation')) {
                throw ValidationException::withMessages([
                    'document' => 'Tài liệu đang có báo cáo vi phạm chờ xử lý. Vui lòng xác nhận để khôi phục và chuyển tài liệu vào hàng đợi kiểm tra.',
                ]);
            }

            $document->restore();
            $document->update([
                'trang_thai' => $hasActiveViolations ? 'CHO_KIEM_TRA' : 'DA_DUYET',
                'ly_do_trang_thai' => $hasActiveViolations
                    ? 'Khôi phục trong khi còn báo cáo vi phạm chờ xử lý.'
                    : null,
            ]);

            LichSuThungRac::create([
                'ma_tai_lieu' => $document->getKey(),
                'ma_nguoi_thuc_hien' => $request->user()->getKey(),
                'hanh_dong' => 'RESTORE',
                'nguon' => $request->user()->laQuanTriVien() ? 'ADMIN' : 'MODERATOR',
                'ly_do' => $request->input('reason', 'Khôi phục tài liệu từ thùng rác.'),
                'chi_tiet' => ['co_bao_cao_cho_xu_ly' => $hasActiveViolations],
            ]);

            return $document;
        });

        return back()->with('success', "Đã khôi phục tài liệu \"{$document->tieu_de}\" thành công.");
    }

    // Xóa vĩnh viễn (Hard Delete / Purge) tài liệu và file vật lý khỏi hệ thống.
    public function permanentlyDeleteDocument(Request $request, int $id): RedirectResponse
    {
        abort_unless($request->user()->laQuanTriVien(), 403, 'Chỉ quản trị viên mới được xóa vĩnh viễn.');

        DB::transaction(function () use ($request, $id): void {
            $document = TaiLieu::onlyTrashed()->lockForUpdate()->findOrFail($id);
            $eligibleAt = $document->han_luu_thung_rac
                ?? $document->ngay_xoa_tam?->copy()->addDays(config('documents.trash_admin_delete_after_days'));
            abort_if($eligibleAt?->isFuture() ?? true, 422, 'Tài liệu chưa đủ 60 ngày để Admin xóa vĩnh viễn.');

            // 1. Lưu vết kiểm toán trước khi bản ghi bị xóa sổ hoàn toàn
            LichSuThungRac::create([
                'ma_tai_lieu' => $document->getKey(),
                'ma_nguoi_thuc_hien' => $request->user()->getKey(),
                'hanh_dong' => 'PERMANENT_DELETE',
                'nguon' => 'ADMIN',
                'ly_do' => $request->input('reason', 'Xóa vĩnh viễn sau thời hạn lưu trữ.'),
                'chi_tiet' => ['duong_dan_tep' => $document->duong_dan_tep],
            ]);

            // 2. Xóa tệp tin vật lý trên đĩa cứng
            Storage::disk('local')->delete($document->duong_dan_tep);

            // 3. Xóa vĩnh viễn dòng dữ liệu trong cơ sở dữ liệu
            $document->forceDelete();
        });

        return back()->with('success', 'Đã xóa vĩnh viễn tài liệu và tệp vật lý.');
    }

    // Hàm trợ giúp nội bộ chuẩn hóa quy trình xóa mềm tài liệu từ Quản trị viên / Kiểm duyệt viên.
    private function softDeleteDocument(TaiLieu $document, \App\Models\NguoiDung $actor, string $reason): void
    {
        $source = $actor->laQuanTriVien() ? 'ADMIN' : 'MODERATOR';

        // Cập nhật mốc đủ điều kiện để Admin xóa thủ công
        $document->forceFill([
            'ma_nguoi_xoa' => $actor->getKey(),
            'ly_do_xoa' => $reason,
            'nguon_xoa' => $source,
            'han_luu_thung_rac' => now()->addDays(config('documents.trash_admin_delete_after_days')),
        ])->save();

        // Kích hoạt Soft Delete (ngay_xoa_tam = now())
        $document->delete();

        // Ghi nhận nhật ký thùng rác
        LichSuThungRac::create([
            'ma_tai_lieu' => $document->getKey(),
            'ma_nguoi_thuc_hien' => $actor->getKey(),
            'hanh_dong' => 'SOFT_DELETE',
            'nguon' => $source,
            'ly_do' => $reason,
        ]);
    }
}
