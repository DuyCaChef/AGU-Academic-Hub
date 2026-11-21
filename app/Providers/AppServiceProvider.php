<?php

namespace App\Providers;

use App\Events\BountyCancelledEvent;
use App\Events\BountyFulfillmentConfirmedEvent;
use App\Events\DocumentContributedToBountyEvent;
use App\Events\DocumentModeratedEvent;
use App\Events\KycDecisionEvent;
use App\Events\KycSubmittedEvent;
use App\Events\ReviewReportedEvent;
use App\Listeners\BountyNotificationListener;
use App\Listeners\DocumentModerationNotificationListener;
use App\Listeners\KycNotificationListener;
use App\Listeners\ReviewNotificationListener;
use App\Services\Notifications\Adapters\LaravelNotificationDispatcher;
use App\Services\Notifications\Contracts\NotificationDispatcherInterface;
use App\Services\Notifications\NotificationPayloadBuilder;
use App\Services\Notifications\NotificationRecipientPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            NotificationDispatcherInterface::class,
            LaravelNotificationDispatcher::class
        );

        $this->app->singleton(NotificationPayloadBuilder::class);
        $this->app->singleton(NotificationRecipientPolicy::class);
    }

    // Bootstrap các dịch vụ ứng dụng
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
        Schema::defaultStringLength(191);
        $this->registerEventListeners();
    }

    private function registerEventListeners(): void
    {
        Event::listen(
            DocumentContributedToBountyEvent::class,
            [BountyNotificationListener::class, 'handleDocumentContributed']
        );
        Event::listen(
            BountyFulfillmentConfirmedEvent::class,
            [BountyNotificationListener::class, 'handleBountyConfirmed']
        );
        Event::listen(
            BountyCancelledEvent::class,
            [BountyNotificationListener::class, 'handleBountyCancelled']
        );
        Event::listen(
            DocumentModeratedEvent::class,
            [DocumentModerationNotificationListener::class, 'handle']
        );
        Event::listen(
            ReviewReportedEvent::class,
            [ReviewNotificationListener::class, 'handle']
        );
        Event::listen(
            KycSubmittedEvent::class,
            [KycNotificationListener::class, 'handleKycSubmitted']
        );
        Event::listen(
            KycDecisionEvent::class,
            [KycNotificationListener::class, 'handleKycDecision']
        );
    }
}

