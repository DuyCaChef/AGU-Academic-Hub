<?php

namespace App\Modules\Moderation\Application\Actions;

use App\Models\BaoCaoViPham;
use App\Models\LichSuThungRac;
use App\Models\NguoiDung;
use App\Models\TaiLieu;
use App\Notifications\ViolationReportResolvedNotification;
use App\Modules\Moderation\Domain\Services\DocumentReportRecalculator;
use Illuminate\Support\Facades\DB;

class ResolveViolationReportAction
{
    public function __construct(
        protected DocumentReportRecalculator $recalculator
    ) {}

    // Quyết định giải quyết báo cáo vi phạm: Duyệt ẩn, xóa mềm hoặc bác bỏ
    public function execute(int $reportId, string $action, string $reason, NguoiDung $actor): string
    {
        return DB::transaction(function () use ($reportId, $action, $reason, $actor): string {
            $report = BaoCaoViPham::with(['taiLieu', 'nguoiBaoCao'])->findOrFail($reportId);
            $document = TaiLieu::withTrashed()->findOrFail($report->ma_tai_lieu);
            $now = now();
            $status = ($action === 'DISMISS') ? 'BAC_BO' : 'DA_XU_LY';

            $actionLabel = match ($action) {
                'HIDE'   => 'ẩn để kiểm tra',
                'DELETE' => 'xóa mềm',
                default  => 'bác bỏ',
            };

            // 1. Áp dụng hành động đối với tài liệu
            if ($action === 'HIDE') {
                $document->update(['trang_thai' => 'TAM_AN_KIEM_TRA']);
            } elseif ($action === 'DELETE' && !$document->trashed()) {
                $source = $actor->laQuanTriVien() ? 'ADMIN' : 'MODERATOR';

                $document->forceFill([
                    'ma_nguoi_xoa'      => $actor->getKey(),
                    'ly_do_xoa'         => $reason,
                    'nguon_xoa'         => $source,
                    'han_luu_thung_rac' => now()->addDays(config('documents.trash_admin_delete_after_days', 60)),
                ])->save();

                $document->delete();

                LichSuThungRac::create([
                    'ma_tai_lieu'        => $document->getKey(),
                    'ma_nguoi_thuc_hien' => $actor->getKey(),
                    'hanh_dong'          => 'SOFT_DELETE',
                    'nguon'              => $source,
                    'ly_do'              => $reason,
                ]);
            }

            // 2. Cập nhật các bản ghi báo cáo
            $reportsToResolve = ($action === 'DISMISS')
                ? collect([$report])
                : BaoCaoViPham::with('nguoiBaoCao')
                    ->where('ma_tai_lieu', $report->ma_tai_lieu)
                    ->where('trang_thai_xu_ly', 'CHO_XU_LY')
                    ->get();

            foreach ($reportsToResolve as $relatedReport) {
                $relatedReport->update([
                    'trang_thai_xu_ly' => $status,
                    'ma_nguoi_xu_ly'   => $actor->getKey(),
                    'ly_do_xu_ly'      => $reason,
                    'xu_ly_luc'        => $now,
                ]);
            }

            // 3. Tính toán lại số báo cáo hợp lệ
            $this->recalculator->recalculate($document);

            // 4. Gửi thông báo đến tác giả và những người đã báo cáo
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
    }
}

