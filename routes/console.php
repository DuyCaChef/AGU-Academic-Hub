<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('documents:purge-trash')->daily();
Schedule::command('documents:notify-expiring-trash')->daily();
// Tự động hủy và hoàn điểm cọc cho các yêu cầu Bounty quá 30 ngày không có phản hồi
Schedule::command('bounty:auto-refund')->dailyAt('02:00');
