<?php

declare(strict_types=1);

namespace Domain\Sales\Application\Handlers;

use App\Support\Transactions\Atomic;
use Carbon\CarbonImmutable;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Sales\Application\Commands\ApproveCheckoutDiscountCommand;
use Domain\Sales\Domain\Repositories\SalesCheckoutRepository;
use Domain\Sales\Domain\ValueObjects\CheckoutId;
use Domain\Shared\Domain\ValueObjects\StaffId;

/**
 * MKT-BR-15: the separate approval step — gated behind `sales.approve-discount`
 * at the route level, distinct from `sales.create` (mirrors Warranty's
 * assess/approve and refund-approve/process separations).
 */
final class ApproveCheckoutDiscountHandler
{
    public function __construct(
        private readonly SalesCheckoutRepository $checkouts,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(ApproveCheckoutDiscountCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $checkout = $this->checkouts->lockForUpdate(new CheckoutId($command->checkoutId));

            $checkout->approveAdjustment($command->approvedByStaffId, CarbonImmutable::now());
            $this->checkouts->save($checkout);

            $this->audit->record(
                module: 'Sales',
                eventType: 'CheckoutDiscountApproved',
                actorStaffId: new StaffId($command->approvedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'sales_checkout',
                subjectId: $command->checkoutId,
                beforeState: null,
                afterState: ['approved_by_staff_id' => $command->approvedByStaffId],
            );
        });
    }
}
