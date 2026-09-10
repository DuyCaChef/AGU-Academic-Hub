<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Inertia::render('Home');
});

foreach (glob(app_path('Modules/*/routes.php')) as $moduleRoutes) {
    require $moduleRoutes;
}
