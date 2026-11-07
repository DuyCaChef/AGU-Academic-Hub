<?php

namespace App\Modules\Moderation\Application\Actions;

use App\Models\BaoCaoDanhGia;

class ArchiveReviewReportAction
{
    // Lưu trữ (xóa mềm) bản ghi báo cáo đánh giá đã xử lý hoặc đã bác bỏ khỏi hàng đợi
    public function execute(int $reportId): void
    {
        $report = BaoCaoDanhGia::findOrFail($reportId);

        if (!in_array($report->trang_thai, ['DA_XU_LY', 'BAC_BO'], true)) {
            abort(422, 'Chỉ có thể lưu trữ báo cáo đã xử lý hoặc đã bác bỏ.');
        }

        $report->delete();
    }
}

