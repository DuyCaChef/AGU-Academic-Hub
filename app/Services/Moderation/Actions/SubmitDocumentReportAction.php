<?php

namespace App\Services\Moderation\Actions;

use App\Models\BaoCaoViPham;
use App\Models\NguoiDung;
use App\Services\Moderation\DocumentReportService;

class SubmitDocumentReportAction extends DocumentReportService
{
    public function execute(int $documentId, array $data, NguoiDung $reporter): BaoCaoViPham
    {
        return $this->submit($documentId, $data, $reporter);
    }
}

