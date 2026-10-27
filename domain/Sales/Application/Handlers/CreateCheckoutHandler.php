<?php

declare(strict_types=1);

namespace Domain\Sales\Application\Handlers;

use App\Support\Transactions\Atomic;
use Carbon\CarbonImmutable;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Identity\Application\Contracts\CustomerDirectoryService;
use Domain\Sales\Application\Commands\CreateCheckoutCommand;
use Domain\Sales\Domain\Entities\SalesCheckout;
use Domain\Sales\Domain\Exceptions\CustomerCartReservedAtAnotherShop;
use Domain\Sales\Domain\Exceptions\WarehouseCannotSell;
use Domain\Sales\Domain\Repositories\SalesCheckoutRepository;
use Domain\Sales\Domain\ValueObjects\CheckoutId;
use Domain\Shared\Domain\Contracts\ShopClassification;
use Domain\Shared\Domain\ValueObjects\CustomerId;
use Domain\Shared\Domain\ValueObjects\ShopId;
use Domain\Shared\Domain\ValueObjects\StaffId;

/** Creates the checkout shell — no items yet, reservation window starts immediately (BLD §4.4: independently configurable per-checkout timer). */
final class CreateCheckoutHandler
{
    public function __construct(
        private readonly SalesCheckoutRepository $checkouts,
        private readonly ShopClassification $shops,
        private readonly CustomerDirectoryService $customerDirectory,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(CreateCheckoutCommand $command): CheckoutId
    {
        if ($this->shops->isWarehouse(new ShopId($command->shopId))) {
            throw WarehouseCannotSell::forShop($command->shopId);
        }

        // A walk-in's contact details become a real (passwordless) customer record too — purely so they're findable on a later visit; this checkout's own customer_id is left exactly as given, never silently repointed at the new record.
        if ($command->customerId === null && $command->walkInName !== null && $command->walkInPhone !== null) {
            $this->customerDirectory->findOrCreateWalkIn($command->walkInName, $command->walkInPhone, $command->walkInEmail);
        }

        return $this->atomic->run(function () use ($command): CheckoutId {
            $cashierStaffId = $command->cashierStaffId !== null ? new StaffId($command->cashierStaffId) : null;

            if ($cashierStaffId !== null && ! $command->forceNew) {
                $existing = $this->checkouts->findOpenIdForCashier($cashierStaffId);

                if ($existing !== null) {
                    return $existing;
                }

                // Before starting a fresh checkout at the till, check whether this customer already has their own self-service cart waiting to be claimed — avoids a second, orphaned reservation for the same person.
                if ($command->customerId !== null) {
                    $claimed = $this->claimExistingCustomerCart(new CustomerId($command->customerId), new ShopId($command->shopId), $cashierStaffId);

                    if ($claimed !== null) {
                        return $claimed;
                    }
                }
            } elseif ($command->customerId !== null) {
                // A pure customer self-service open (no till involved) — resume their own open cart instead of starting a second one. Still shop-scoped like any checkout: a second product from a different shop can't silently join it.
                $existing = $this->checkouts->findOpenIdForCustomer(new CustomerId($command->customerId));

                if ($existing !== null) {
                    $cart = $this->checkouts->get($existing);

                    if (! $cart->shopId()->equals(new ShopId($command->shopId))) {
                        throw CustomerCartReservedAtAnotherShop::forCheckout($existing->value);
                    }

                    return $existing;
                }
            }

            $expiresAt = $cashierStaffId !== null
                ? CarbonImmutable::now()->addMinutes((int) config('afprospos.checkout_reservation_minutes'))
                : CarbonImmutable::now()->addHours((int) config('afprospos.customer_cart_reservation_hours'));

            $checkout = SalesCheckout::open(
                new ShopId($command->shopId),
                $command->customerId !== null ? new CustomerId($command->customerId) : null,
                $cashierStaffId,
                $expiresAt,
                $command->walkInName,
                $command->walkInPhone,
                $command->walkInEmail,
            );

            $id = $this->checkouts->save($checkout);

            $this->audit->record(
                module: 'Sales',
                eventType: 'CheckoutCreated',
                actorStaffId: $cashierStaffId,
                actorRoleSnapshot: null,
                subjectType: 'sales_checkout',
                subjectId: $id->value,
                beforeState: null,
                afterState: ['shop_id' => $command->shopId, 'customer_id' => $command->customerId, 'reservation_expires_at' => $expiresAt->toDateTimeString()],
            );

            return $id;
        });
    }

    /** Returns the claimed checkout's id, or null when the customer has no open cart to claim. */
    private function claimExistingCustomerCart(CustomerId $customerId, ShopId $shopId, StaffId $cashierStaffId): ?CheckoutId
    {
        $customerCartId = $this->checkouts->findOpenIdForCustomer($customerId);

        if ($customerCartId === null) {
            return null;
        }

        $cart = $this->checkouts->lockForUpdate($customerCartId);

        if (! $cart->shopId()->equals($shopId)) {
            throw CustomerCartReservedAtAnotherShop::forCheckout($customerCartId->value);
        }

        if ($cart->cashierStaffId() === null) {
            $cart->assignCashier($cashierStaffId);
            $this->checkouts->save($cart);
        }

        return $customerCartId;
    }
}
