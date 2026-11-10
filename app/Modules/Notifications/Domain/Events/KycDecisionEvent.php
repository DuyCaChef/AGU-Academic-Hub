<?php

namespace App\Modules\Notifications\Domain\Events;

use App\Models\NguoiDung;
use App\Models\YeuCauXacThuc;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class KycDecisionEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly YeuCauXacThuc $kyc,
        public readonly string $decision, // 'DA_DUYET' hoặc 'TU_CHOI'
        public readonly ?string $reason,
        public readonly NguoiDung $reviewer
    ) {}
}

