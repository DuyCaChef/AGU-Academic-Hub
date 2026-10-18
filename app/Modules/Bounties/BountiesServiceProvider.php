<?php

namespace App\Modules\Bounties;

use App\Shared\Support\Module;

class BountiesServiceProvider extends Module
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/routes.php');
    }
}