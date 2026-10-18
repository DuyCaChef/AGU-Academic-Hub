<?php

namespace App\Modules\Moderation;

use App\Shared\Support\Module;

class ModerationServiceProvider extends Module
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/routes.php');
    }
}