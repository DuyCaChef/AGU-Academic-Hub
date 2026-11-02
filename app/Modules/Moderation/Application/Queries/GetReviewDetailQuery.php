<?php

namespace App\Modules\Moderation\Application\Queries;

use App\Models\BaoCaoViPham;
use App\Models\TaiLieu;
use Illuminate\Support\Facades\Storage;

class GetReviewDetailQuery
{
    // Lấy thông tin chi tiết tài liệu trong hàng đợi kiểm tra (kèm các báo cáo và trạng thái file)
    public function execute(int $documentId): array
    {
        $document = TaiLieu::withTrashed()
            ->with(['nguoiDang', 'monHoc'])
            ->findOrFail($documentId);

        $reports = BaoCaoViPham::with('nguoiBaoCao')
            ->where('ma_tai_lieu', $document->getKey())
            ->latest('ma_bao_cao')
            ->get();

        $fileExists = Storage::disk('local')->exists($document->duong_dan_tep);

        return [
            'document'   => $document,
            'reports'    => $reports,
            'fileExists' => $fileExists,
        ];
    }
}

