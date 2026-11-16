<?php

namespace App\Services\Moderation;

use App\Models\BaoCaoViPham;
use App\Models\NguoiDung;
use App\Models\TaiLieu;

class DocumentReportService
{
    public function __construct(
        protected DocumentReportRecalculator $recalculator
    ) {}

    public function submit(int $documentId, array $data, NguoiDung $reporter): BaoCaoViPham
    {
        $document = TaiLieu::query()->findOrFail($documentId);

        return $this->recalculator->createReport($document, $reporter, $data);
    }
}
