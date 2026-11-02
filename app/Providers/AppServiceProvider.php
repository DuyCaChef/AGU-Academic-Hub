<?php

namespace App\Providers;

use App\Modules\AcademicCatalog\AcademicCatalogServiceProvider;
use App\Modules\Administration\AdministrationServiceProvider;
use App\Modules\Documents\DocumentsServiceProvider;
use App\Modules\Identity\IdentityServiceProvider;
use App\Modules\Moderation\ModerationServiceProvider;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->register(AdministrationServiceProvider::class);
        $this->app->register(AcademicCatalogServiceProvider::class);
        $this->app->register(IdentityServiceProvider::class);
        $this->app->register(DocumentsServiceProvider::class);
        $this->app->register(ModerationServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
        Schema::defaultStringLength(191);
    }
}
