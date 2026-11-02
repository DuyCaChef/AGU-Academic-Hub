<?php

namespace App\Modules\Administration;

use App\Shared\Support\Module;
use Illuminate\Support\Facades\Route;

class AdministrationServiceProvider extends Module
{
    public function boot(): void
    {
        Route::middleware('web')
            ->group(__DIR__ . '/routes.php');
    }
}
