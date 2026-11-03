<?php

namespace App\Notifications;

use App\Modules\Identity\Infrastructure\Notifications\AlumniKycDecisionNotification as ModuleNotification;

/**
 * Lớp tương thích ngược cho App\Notifications\KycDecisionNotification.
 * Toàn bộ logic chính thức nằm tại App\Modules\Identity\Infrastructure\Notifications\AlumniKycDecisionNotification.
 */
class KycDecisionNotification extends ModuleNotification
{
}
