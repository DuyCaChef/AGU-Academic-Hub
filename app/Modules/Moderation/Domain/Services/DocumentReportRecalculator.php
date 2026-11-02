<?php

namespace App\Modules\Moderation\Domain\Services;

use App\Models\BaoCaoViPham;
use App\Models\NguoiDung;
use App\Models\TaiLieu;
use App\Notifications\DocumentReviewRequiredNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DocumentReportRecalculator
{
    // Tạo bản ghi báo cáo vi phạm mới (chống gửi trùng từ cùng một tài khoản)
    public function createReport(TaiLieu $document, NguoiDung $reporter, array $data): BaoCaoViPham
    {
        return DB::transaction(function () use ($document, $reporter, $data): BaoCaoViPham {
            $lockedDoc = TaiLieu::query()->lockForUpdate()->findOrFail($document->getKey());

            if (BaoCaoViPham::withTrashed()
                ->where('ma_tai_lieu', $lockedDoc->getKey())
                ->where('ma_nguoi_bao_cao', $reporter->getKey())
                ->exists()) {
                throw ValidationException::withMessages([
                    'report' => 'Bạn đã báo cáo tài liệu này. Mỗi tài khoản chỉ được gửi một báo cáo cho một tài liệu.',
                ]);
            }

            $report = $lockedDoc->baoCaoViPhams()->create([
                'ma_nguoi_bao_cao' => $reporter->getKey(),
                'ly_do'            => $data['ly_do'],
                'chi_tiet_loi'     => $data['chi_tiet_loi'] ?? null,
            ]);

            $this->recalculate($lockedDoc, true);

            return $report;
        });
    }

    // Tính toán lại số báo cáo hợp lệ và kích hoạt chuyển hàng đợi kiểm tra hoặc ẩn tự động
    public function recalculate(TaiLieu $document, bool $notify = false): TaiLieu
    {
        $document->loadMissing('baoCaoViPhams.nguoiBaoCao');
        $validReports = $document->baoCaoViPhams
            ->filter(fn (BaoCaoViPham $report): bool => $report->laHopLe())
            ->count();

        $wasQueued = in_array($document->trang_thai, ['CHO_KIEM_TRA', 'TAM_AN_KIEM_TRA'], true);
        $autoHidden = $validReports >= config('moderation.auto_hide_report_threshold', 5);
        $thresholdReached = $validReports >= config('moderation.review_report_threshold', 3);

        $document->so_bao_cao_hop_le = $validReports;

        if ($autoHidden) {
            $document->trang_thai = 'TAM_AN_KIEM_TRA';
            $document->ly_do_trang_thai = "Tự động ẩn: đạt {$validReports} báo cáo hợp lệ từ tài khoản uy tín.";
        } elseif ($thresholdReached && !$wasQueued) {
            $document->trang_thai = 'CHO_KIEM_TRA';
            $document->ly_do_trang_thai = "Tự động chuyển chờ kiểm tra: đạt {$validReports} báo cáo hợp lệ từ tài khoản uy tín.";
        }

        if (($autoHidden || ($thresholdReached && !$wasQueued)) && !$document->tu_dong_chuyen_luc) {
            $document->tu_dong_chuyen_luc = now();
        }

        $document->save();

        if ($notify && ($autoHidden || ($thresholdReached && !$wasQueued))) {
            $recipients = NguoiDung::query()
                ->whereIn('vai_tro', ['KIEM_DUYET_VIEN', 'QUAN_TRI_VIEN'])
                ->get();

            $recipients->each(fn (NguoiDung $recipient) => $recipient->notify(
                new DocumentReviewRequiredNotification($document, $validReports, $autoHidden)
            ));
        }

        return $document;
    }

    // Query builder các báo cáo hợp lệ từ tài khoản uy tín
    public function validReportsQuery(): Builder
    {
        return BaoCaoViPham::query()
            ->where('trang_thai_xu_ly', '!=', 'BAC_BO')
            ->whereHas('nguoiBaoCao', fn (Builder $query) => $query
                ->where('diem_uy_tin', '>=', config('moderation.trusted_reporter_reputation', 100)));
    }
}
