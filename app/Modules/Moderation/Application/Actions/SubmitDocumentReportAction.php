<?php

namespace App\Modules\Moderation\Application\Actions;

use App\Models\BaoCaoViPham;
use App\Models\NguoiDung;
use App\Models\TaiLieu;
use App\Modules\Moderation\Domain\Services\DocumentReportRecalculator;

class SubmitDocumentReportAction
{
    public function __construct(
        protected DocumentReportRecalculator $recalculator
    ) {}

    // Gửi báo cáo vi phạm tài liệu mới
    public function execute(int $documentId, array $data, NguoiDung $reporter): BaoCaoViPham
    {
        $document = TaiLieu::query()->findOrFail($documentId);

        return $this->recalculator->createReport($document, $reporter, $data);
    }
}

