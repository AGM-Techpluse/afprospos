<?php

declare(strict_types=1);

namespace Domain\Sales\Application\Handlers;

use App\Support\Transactions\Atomic;
use Carbon\CarbonImmutable;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Sales\Application\Commands\CreateCheckoutCommand;
use Domain\Sales\Domain\Entities\SalesCheckout;
use Domain\Sales\Domain\Repositories\SalesCheckoutRepository;
use Domain\Sales\Domain\ValueObjects\CheckoutId;
use Domain\Shared\Domain\ValueObjects\CustomerId;
use Domain\Shared\Domain\ValueObjects\ShopId;
use Domain\Shared\Domain\ValueObjects\StaffId;

/** Creates the checkout shell — no items yet, reservation window starts immediately (BLD §4.4: independently configurable per-checkout timer). */
final class CreateCheckoutHandler
{
    public function __construct(
        private readonly SalesCheckoutRepository $checkouts,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(CreateCheckoutCommand $command): CheckoutId
    {
        return $this->atomic->run(function () use ($command): CheckoutId {
            $expiresAt = CarbonImmutable::now()->addMinutes((int) config('afprospos.checkout_reservation_minutes'));

            $checkout = SalesCheckout::open(
                new ShopId($command->shopId),
                $command->customerId !== null ? new CustomerId($command->customerId) : null,
                new StaffId($command->cashierStaffId),
                $expiresAt,
            );

            $id = $this->checkouts->save($checkout);

            $this->audit->record(
                module: 'Sales',
                eventType: 'CheckoutCreated',
                actorStaffId: new StaffId($command->cashierStaffId),
                actorRoleSnapshot: null,
                subjectType: 'sales_checkout',
                subjectId: $id->value,
                beforeState: null,
                afterState: ['shop_id' => $command->shopId, 'customer_id' => $command->customerId, 'reservation_expires_at' => $expiresAt->toDateTimeString()],
            );

            return $id;
        });
    }
}
