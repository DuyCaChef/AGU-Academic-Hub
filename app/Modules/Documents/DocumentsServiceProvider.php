<?php

namespace App\Modules\Documents;

use App\Shared\Support\Module;

class DocumentsServiceProvider extends Module
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/routes.php');
    }
}