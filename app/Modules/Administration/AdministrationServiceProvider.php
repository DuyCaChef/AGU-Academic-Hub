<?php

namespace App\Modules\Administration;

use App\Shared\Support\Module;

class AdministrationServiceProvider extends Module
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/routes.php');
    }
}