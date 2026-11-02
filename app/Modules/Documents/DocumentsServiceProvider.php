<?php

namespace App\Modules\Documents;

use App\Shared\Support\Module;
use Illuminate\Support\Facades\Route;

class DocumentsServiceProvider extends Module
{
    // Đăng ký routes của Module Documents với middleware web
    public function boot(): void
    {
        Route::middleware('web')
            ->group(__DIR__ . '/routes.php');
    }
}
