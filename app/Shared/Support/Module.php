<?php

namespace App\Shared\Support;

use Illuminate\Support\ServiceProvider;

abstract class Module extends ServiceProvider
{
    public function register(): void
    {
        // Register module bindings in the concrete module service provider.
    }

    public function boot(): void
    {
        // Load module routes, migrations, translations and views here.
    }
}
