<?php

namespace App\Modules\Identity;

use App\Shared\Support\Module;
use Illuminate\Support\Facades\Route;

class IdentityServiceProvider extends Module
{
    // Đăng ký routes của Module Identity với middleware web
    public function boot(): void
    {
        Route::middleware('web')
            ->group(__DIR__ . '/routes.php');
    }
}
