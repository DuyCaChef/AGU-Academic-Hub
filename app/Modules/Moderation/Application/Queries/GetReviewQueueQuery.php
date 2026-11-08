<?php

namespace App\Modules\Moderation\Application\Queries;

use App\Models\TaiLieu;

// Query lấy danh sách tài liệu trong hàng đợi kiểm duyệt (Hỗ trợ tài liệu mới CHO_DUYET và tài liệu bị báo cáo)
class GetReviewQueueQuery
{
    public function execute(array $filters = []): array
    {
        $trustedReputation = config('moderation.trusted_reporter_reputation', 100);
        $reviewThreshold = config('moderation.review_report_threshold', 3);
        $autoHideThreshold = config('moderation.auto_hide_report_threshold', 5);

        // Đếm số lượng tài liệu theo từng nhóm trạng thái phục vụ hiển thị Badge trên Tab
        $counts = [
            'all'             => TaiLieu::whereIn('trang_thai', ['CHO_DUYET', 'CHO_KIEM_TRA', 'TAM_AN_KIEM_TRA'])->count(),
            'CHO_DUYET'       => TaiLieu::where('trang_thai', 'CHO_DUYET')->count(),
            'CHO_KIEM_TRA'    => TaiLieu::where('trang_thai', 'CHO_KIEM_TRA')->count(),
            'TAM_AN_KIEM_TRA' => TaiLieu::where('trang_thai', 'TAM_AN_KIEM_TRA')->count(),
        ];

        $tab = $filters['tab'] ?? $filters['status'] ?? 'all';

        $query = TaiLieu::query()
            ->with([
                'nguoiDang:ma_nguoi_dung,ho_ten,mssv,anh_dai_dien',
                'monHoc:ma_mon_hoc,ma_hoc_phan,ten_mon_hoc',
                'dapUngYeuCaus.yeuCau:ma_yeu_cau,tieu_de,diem_thuong',
            ])
            ->withCount([
                'baoCaoViPhams as bao_cao_hop_le_count' => fn($q) => $q
                    ->where('trang_thai_xu_ly', '!=', 'BAC_BO')
                    ->whereHas('nguoiBaoCao', fn($reporter) => $reporter
                        ->where('diem_uy_tin', '>=', $trustedReputation))
            ]);

        // Áp dụng bộ lọc theo Tab trạng thái
        if ($tab === 'CHO_DUYET') {
            $query->where('trang_thai', 'CHO_DUYET')
                ->latest('created_at');
        } elseif ($tab === 'CHO_KIEM_TRA') {
            $query->where('trang_thai', 'CHO_KIEM_TRA')
                ->orderByDesc('so_bao_cao_hop_le')
                ->latest('tu_dong_chuyen_luc');
        } elseif ($tab === 'TAM_AN_KIEM_TRA') {
            $query->where('trang_thai', 'TAM_AN_KIEM_TRA')
                ->orderByDesc('so_bao_cao_hop_le')
                ->latest('tu_dong_chuyen_luc');
        } else {
            // Mặc định: Lấy tất cả tài liệu cần xử lý
            $query->whereIn('trang_thai', ['CHO_DUYET', 'CHO_KIEM_TRA', 'TAM_AN_KIEM_TRA'])
                ->orderByRaw("CASE WHEN trang_thai = 'CHO_DUYET' THEN 0 WHEN trang_thai = 'TAM_AN_KIEM_TRA' THEN 1 ELSE 2 END")
                ->latest('created_at');
        }

        $documents = $query->paginate(15)->withQueryString();

        return [
            'documents'         => $documents,
            'counts'            => $counts,
            'currentTab'        => $tab,
            'trustedReputation' => $trustedReputation,
            'reviewThreshold'   => $reviewThreshold,
            'autoHideThreshold' => $autoHideThreshold,
        ];
    }
}
