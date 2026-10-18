<?php

namespace App\Modules\AcademicCatalog;

use App\Shared\Support\Module;

class AcademicCatalogServiceProvider extends Module
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/routes.php');
    }
}