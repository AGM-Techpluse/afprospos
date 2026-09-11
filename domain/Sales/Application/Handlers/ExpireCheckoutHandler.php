<?php

declare(strict_types=1);

namespace Domain\Sales\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Inventory\Application\Commands\ReleaseInventoryCommand;
use Domain\Inventory\Application\Contracts\InventoryReservationService;
use Domain\Sales\Application\Commands\ExpireCheckoutCommand;
use Domain\Sales\Domain\Repositories\SalesCheckoutRepository;
use Domain\Sales\Domain\ValueObjects\CheckoutId;

/**
 * The expiry worker's per-checkout unit of work (DBDD §26, SALE/INV-BR-06).
 * Idempotent: locks first, then checks status — a checkout already
 * expired/paid/cancelled by the time this runs (a concurrent payment
 * confirmation won the race) is silently skipped, never double-released.
 */
final class ExpireCheckoutHandler
{
    public function __construct(
        private readonly SalesCheckoutRepository $checkouts,
        private readonly InventoryReservationService $reservations,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(ExpireCheckoutCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $checkout = $this->checkouts->lockForUpdate(new CheckoutId($command->checkoutId));

            if ($checkout->status() !== 'open') {
                return;
            }

            foreach ($checkout->items() as $item) {
                $this->reservations->release(new ReleaseInventoryCommand(
                    skuId: $item->skuId(),
                    shopId: $checkout->shopId()->value,
                    quantity: $item->quantity(),
                    sourceType: 'checkout',
                    sourceId: $command->checkoutId,
                ));
            }

            $checkout->markExpired();
            $this->checkouts->save($checkout);

            $this->audit->record(
                module: 'Sales',
                eventType: 'CheckoutExpired',
                actorStaffId: null,
                actorRoleSnapshot: null,
                subjectType: 'sales_checkout',
                subjectId: $command->checkoutId,
                beforeState: null,
                afterState: ['item_count' => count($checkout->items())],
            );
        });
    }
}
