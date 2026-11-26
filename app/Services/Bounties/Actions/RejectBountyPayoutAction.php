<?php

namespace App\Services\Bounties\Actions;

use App\Enums\TrangThaiYeuCauTaiLieu;
use App\Models\NguoiDung;
use App\Enums\HanhDongKiemToan;
use App\Enums\LoaiDoiTuongKiemToan;
use App\Services\Admin\AuditLogger;
use App\Services\Wallet\WalletLedgerEvaluator as ReputationEvaluator;
use App\Notifications\BountyPayoutRejectedNotification;
use App\Services\Bounties\Repositories\WishlistBountyRepository;
use Illuminate\Support\Facades\DB;

// Compatibility wrapper; nghiệp vụ chính nằm trong RejectBountyPayoutService.
class RejectBountyPayoutAction extends \App\Services\Bounties\RejectBountyPayoutService
{
}


