<?php

declare(strict_types=1);

namespace Domain\Sales\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Sales\Application\Commands\ApplyDiscountCommand;
use Domain\Sales\Domain\Entities\SalesCheckoutAdjustment;
use Domain\Sales\Domain\Repositories\SalesCheckoutRepository;
use Domain\Sales\Domain\ValueObjects\CheckoutId;
use Domain\Shared\Domain\ValueObjects\Money;

final class ApplyDiscountHandler
{
    public function __construct(
        private readonly SalesCheckoutRepository $checkouts,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(ApplyDiscountCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $checkout = $this->checkouts->lockForUpdate(new CheckoutId($command->checkoutId));

            $adjustment = SalesCheckoutAdjustment::create($command->type, $command->sourceId, new Money($command->amountMinor));
            $checkout->applyAdjustment($adjustment);

            $this->checkouts->save($checkout);

            $this->audit->record(
                module: 'Sales',
                eventType: 'CheckoutDiscountApplied',
                actorStaffId: $checkout->cashierStaffId(),
                actorRoleSnapshot: null,
                subjectType: 'sales_checkout',
                subjectId: $command->checkoutId,
                beforeState: null,
                afterState: ['type' => $command->type, 'source_id' => $command->sourceId, 'amount_minor' => $command->amountMinor],
            );
        });
    }
}
