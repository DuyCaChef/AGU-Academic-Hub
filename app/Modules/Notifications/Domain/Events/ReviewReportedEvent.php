<?php

namespace App\Modules\Notifications\Domain\Events;

use App\Models\BaoCaoDanhGia;
use App\Models\DanhGiaMonHoc;
use App\Models\NguoiDung;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReviewReportedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly BaoCaoDanhGia $report,
        public readonly DanhGiaMonHoc $review,
        public readonly NguoiDung $reporter
    ) {}
}

