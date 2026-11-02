<?php

namespace App\Modules\Moderation\Application\Actions;

use App\Models\BaoCaoViPham;

class ArchiveViolationReportAction
{
    // Lưu trữ (Archive/Xóa mềm) bản ghi báo cáo đã xử lý hoặc đã bác bỏ
    public function execute(int $reportId): void
    {
        $report = BaoCaoViPham::findOrFail($reportId);

        if (!in_array($report->trang_thai_xu_ly, ['DA_XU_LY', 'BAC_BO'], true)) {
            abort(422, 'Chỉ có thể lưu trữ báo cáo đã xử lý hoặc đã bác bỏ.');
        }

        $report->delete();
    }
}

