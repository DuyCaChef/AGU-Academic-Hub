<?php

namespace App\Modules\Notifications\Domain\Events;

use App\Models\DapUngYeuCauTaiLieu;
use App\Models\NguoiDung;
use App\Models\YeuCauTaiLieu;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DocumentContributedToBountyEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly DapUngYeuCauTaiLieu $dapUng,
        public readonly YeuCauTaiLieu $yeuCau,
        public readonly NguoiDung $contributor
    ) {}
}

