<?php

namespace App\Modules\Notifications;

use App\Modules\Notifications\Application\Contracts\NotificationDispatcherInterface;
use App\Modules\Notifications\Domain\Events\BountyCancelledEvent;
use App\Modules\Notifications\Domain\Events\BountyFulfillmentConfirmedEvent;
use App\Modules\Notifications\Domain\Events\DocumentContributedToBountyEvent;
use App\Modules\Notifications\Domain\Events\DocumentModeratedEvent;
use App\Modules\Notifications\Domain\Events\KycDecisionEvent;
use App\Modules\Notifications\Domain\Events\KycSubmittedEvent;
use App\Modules\Notifications\Domain\Events\ReviewReportedEvent;
use App\Modules\Notifications\Domain\Services\NotificationPayloadBuilder;
use App\Modules\Notifications\Domain\Services\NotificationRecipientPolicy;
use App\Modules\Notifications\Infrastructure\Adapters\LaravelNotificationDispatcher;
use App\Modules\Notifications\Infrastructure\Listeners\BountyNotificationListener;
use App\Modules\Notifications\Infrastructure\Listeners\DocumentModerationNotificationListener;
use App\Modules\Notifications\Infrastructure\Listeners\KycNotificationListener;
use App\Modules\Notifications\Infrastructure\Listeners\ReviewNotificationListener;
use App\Shared\Support\Module;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;

class NotificationsServiceProvider extends Module
{
    public function register(): void
    {
        // Ràng buộc Singleton cho Dispatcher và Domain Services
        $this->app->singleton(
            NotificationDispatcherInterface::class,
            LaravelNotificationDispatcher::class
        );

        $this->app->singleton(NotificationPayloadBuilder::class);
        $this->app->singleton(NotificationRecipientPolicy::class);
    }

    public function boot(): void
    {
        // 1. Đăng ký routes thuộc phân hệ Notifications
        Route::middleware('web')->group(__DIR__ . '/routes.php');

        // 2. Đăng ký các Domain Events & Notification Listeners
        $this->registerEventListeners();
    }

    protected function registerEventListeners(): void
    {
        // Sự kiện phân hệ Bounty
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

        // Sự kiện kiểm duyệt tài liệu
        Event::listen(
            DocumentModeratedEvent::class,
            [DocumentModerationNotificationListener::class, 'handle']
        );

        // Sự kiện đánh giá môn học
        Event::listen(
            ReviewReportedEvent::class,
            [ReviewNotificationListener::class, 'handle']
        );

        // Sự kiện KYC cựu sinh viên
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
