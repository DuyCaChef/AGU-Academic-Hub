<?php

namespace App\Providers;

use App\Modules\AcademicCatalog\AcademicCatalogServiceProvider;
use App\Modules\Administration\AdministrationServiceProvider;
use App\Modules\Bounties\BountiesServiceProvider;
use App\Modules\Documents\DocumentsServiceProvider;
use App\Modules\Identity\IdentityServiceProvider;
use App\Modules\Moderation\ModerationServiceProvider;
use App\Modules\Reviews\ReviewsServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    // Đăng ký các Module Service Providers theo chuẩn Modular Monolith
    public function register(): void
    {
        $this->app->register(AdministrationServiceProvider::class);
        $this->app->register(AcademicCatalogServiceProvider::class);
        $this->app->register(IdentityServiceProvider::class);
        $this->app->register(DocumentsServiceProvider::class);
        $this->app->register(ModerationServiceProvider::class);
        $this->app->register(ReviewsServiceProvider::class);
        $this->app->register(BountiesServiceProvider::class);
    }

    // Bootstrap các dịch vụ ứng dụng
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
        Schema::defaultStringLength(191);
    }
}
