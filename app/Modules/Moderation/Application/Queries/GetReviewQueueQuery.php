<?php

namespace App\Modules\Moderation\Application\Queries;

use App\Models\TaiLieu;

class GetReviewQueueQuery
{
    // Lấy danh sách tài liệu trong hàng đợi kiểm tra do đạt ngưỡng báo cáo
    public function execute(): array
    {
        $trustedReputation = config('moderation.trusted_reporter_reputation', 100);
        $reviewThreshold = config('moderation.review_report_threshold', 3);
        $autoHideThreshold = config('moderation.auto_hide_report_threshold', 5);

        $documents = TaiLieu::query()
            ->with(['nguoiDang', 'monHoc'])
            ->whereIn('trang_thai', ['CHO_KIEM_TRA', 'TAM_AN_KIEM_TRA'])
            ->withCount([
                'baoCaoViPhams as bao_cao_hop_le_count' => fn($query) => $query
                    ->where('trang_thai_xu_ly', '!=', 'BAC_BO')
                    ->whereHas('nguoiBaoCao', fn($reporter) => $reporter
                        ->where('diem_uy_tin', '>=', $trustedReputation))
            ])
            ->orderByDesc('so_bao_cao_hop_le')
            ->latest('tu_dong_chuyen_luc')
            ->paginate(15)
            ->withQueryString();

        return [
            'documents'         => $documents,
            'trustedReputation' => $trustedReputation,
            'reviewThreshold'   => $reviewThreshold,
            'autoHideThreshold' => $autoHideThreshold,
        ];
    }
}
