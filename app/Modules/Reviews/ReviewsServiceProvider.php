<?php

namespace App\Modules\Reviews;

use App\Shared\Support\Module;
use Illuminate\Support\Facades\Route;

class ReviewsServiceProvider extends Module
{
    public function boot(): void
    {
        // Đăng ký routes thuộc phân hệ Reviews trong middleware web
        Route::middleware('web')->group(__DIR__.'/routes.php');
    }
}
