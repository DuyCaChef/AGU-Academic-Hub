<?php

namespace App\Modules\AcademicCatalog;

use App\Shared\Support\Module;
use Illuminate\Support\Facades\Route;

class AcademicCatalogServiceProvider extends Module
{
    // Đăng ký routes của Module AcademicCatalog với middleware web
    public function boot(): void
    {
        Route::middleware('web')
            ->group(__DIR__ . '/routes.php');
    }
}
