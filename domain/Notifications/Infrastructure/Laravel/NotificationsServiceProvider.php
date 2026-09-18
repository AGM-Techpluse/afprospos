<?php

declare(strict_types=1);

namespace Domain\Notifications\Infrastructure\Laravel;

use Domain\Notifications\Application\Contracts\InAppChannel;
use Domain\Notifications\Application\Contracts\SmsChannel;
use Domain\Notifications\Application\Contracts\WhatsAppChannel;
use Domain\Notifications\Domain\Repositories\NotificationEventRepository;
use Domain\Notifications\Infrastructure\Channels\InApp\AlwaysDeliveredInAppChannel;
use Domain\Notifications\Infrastructure\Channels\Sms\UnconfiguredSmsChannel;
use Domain\Notifications\Infrastructure\Channels\WhatsApp\UnconfiguredWhatsAppChannel;
use Domain\Notifications\Infrastructure\Persistence\Repositories\EloquentNotificationEventRepository;
use Domain\Repair\Domain\Events\RepairCompleted;
use Domain\Repair\Domain\Events\RepairReadyForCollection;
use Domain\Sales\Domain\Events\SaleCompleted;
use Domain\Warranty\Domain\Events\ReturnRequestResolved;
use Domain\Warranty\Domain\Events\TradeInCreditApplied;
use Domain\Warranty\Domain\Events\WarrantyClaimResolved;
use Domain\Warranty\Domain\Events\WarrantyClaimSubmitted;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class NotificationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(NotificationEventRepository::class, EloquentNotificationEventRepository::class);
        $this->app->bind(InAppChannel::class, AlwaysDeliveredInAppChannel::class);
        $this->app->bind(WhatsAppChannel::class, UnconfiguredWhatsAppChannel::class);
        $this->app->bind(SmsChannel::class, UnconfiguredSmsChannel::class);
    }

    /**
     * No dedicated EventServiceProvider exists in this app (none did before
     * this module) — Event::listen from a provider's boot() is idiomatic
     * Laravel and keeps the outbox-bridge wiring next to the module it
     * belongs to, rather than a separate, easy-to-forget registry.
     */
    public function boot(): void
    {
        Event::listen(RepairCompleted::class, Listeners\OnRepairCompleted::class);
        Event::listen(RepairReadyForCollection::class, Listeners\OnRepairReadyForCollection::class);
        Event::listen(WarrantyClaimSubmitted::class, Listeners\OnWarrantyClaimSubmitted::class);
        Event::listen(WarrantyClaimResolved::class, Listeners\OnWarrantyClaimResolved::class);
        Event::listen(SaleCompleted::class, Listeners\OnSaleCompleted::class);
        Event::listen(ReturnRequestResolved::class, Listeners\OnReturnRequestResolved::class);
        Event::listen(TradeInCreditApplied::class, Listeners\OnTradeInCreditApplied::class);
    }
}
