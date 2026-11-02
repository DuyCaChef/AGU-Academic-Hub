<?php

namespace App\Modules\Moderation;

use App\Shared\Support\Module;
use Illuminate\Support\Facades\Route;

class ModerationServiceProvider extends Module
{
    // Đăng ký routes của Module Moderation với middleware web
    public function boot(): void
    {
        Route::middleware('web')
            ->group(__DIR__ . '/routes.php');
    }
}
