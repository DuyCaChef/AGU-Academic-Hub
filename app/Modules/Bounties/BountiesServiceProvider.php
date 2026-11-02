<?php

namespace App\Modules\Bounties;

use App\Shared\Support\Module;
use Illuminate\Support\Facades\Route;

class BountiesServiceProvider extends Module
{
    public function boot(): void
    {
        // Đăng ký routes thuộc phân hệ Bounties trong middleware web
        Route::middleware('web')->group(__DIR__.'/routes.php');
    }
}
