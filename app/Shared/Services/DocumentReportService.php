<?php

namespace App\Shared\Services;

use App\Models\BaoCaoViPham;
use App\Models\NguoiDung;
use App\Models\TaiLieu;
use App\Modules\Moderation\Domain\Services\DocumentReportRecalculator;
use Illuminate\Database\Eloquent\Builder;

class DocumentReportService
{
    public function __construct(
        protected DocumentReportRecalculator $recalculator
    ) {}

    // Tạo báo cáo vi phạm mới
    public function create(TaiLieu $document, NguoiDung $reporter, array $data): BaoCaoViPham
    {
        return $this->recalculator->createReport($document, $reporter, $data);
    }

    // Tính toán lại ngưỡng kiểm duyệt
    public function recalculate(TaiLieu $document, bool $notify = false): TaiLieu
    {
        return $this->recalculator->recalculate($document, $notify);
    }

    // Truy vấn các báo cáo hợp lệ
    public function validReportsQuery(): Builder
    {
        return $this->recalculator->validReportsQuery();
    }
}
