<?php

namespace App\Modules\Notifications\Domain\Events;

use App\Models\NguoiDung;
use App\Models\TaiLieu;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DocumentModeratedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly TaiLieu $document,
        public readonly string $decision, // 'KEEP', 'RESTORE', 'HIDE', 'DELETE'
        public readonly ?string $reason,
        public readonly NguoiDung $moderator
    ) {}
}

