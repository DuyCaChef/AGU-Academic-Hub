<?php

namespace App\Modules\Identity;

use App\Shared\Support\Module;

class IdentityServiceProvider extends Module
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/routes.php');
    }
}