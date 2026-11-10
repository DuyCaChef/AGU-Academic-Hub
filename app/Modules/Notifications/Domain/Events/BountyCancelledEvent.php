<?php

namespace App\Modules\Notifications\Domain\Events;

use App\Models\NguoiDung;
use App\Models\YeuCauTaiLieu;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BountyCancelledEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly YeuCauTaiLieu $yeuCau,
        public readonly NguoiDung $requester,
        public readonly int $refundedPoints,
        public readonly array $contributorIds = []
    ) {}
}

