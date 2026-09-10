<?php

namespace App\Modules\Reviews;

use App\Shared\Support\Module;

class ReviewsServiceProvider extends Module
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/routes.php');
    }
}