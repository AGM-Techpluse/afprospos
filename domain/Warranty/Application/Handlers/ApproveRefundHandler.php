<?php

declare(strict_types=1);

namespace Domain\Warranty\Application\Handlers;

use App\Support\Transactions\Atomic;
use Carbon\CarbonImmutable;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Payments\Application\Commands\RefundPaymentCommand;
use Domain\Payments\Application\Contracts\PaymentRefundService;
use Domain\Shared\Domain\ValueObjects\StaffId;
use Domain\Warranty\Application\Commands\ApproveRefundCommand;
use Domain\Warranty\Domain\Events\WarrantyClaimResolved;
use Domain\Warranty\Domain\Repositories\WarrantyClaimRepository;
use Domain\Warranty\Domain\ValueObjects\WarrantyClaimId;
use Illuminate\Support\Facades\Event;

/** WAR-BR-06/09: refund is requested at SelectWarrantyClaimRemedyCommand time; this Command is the separate authorization+execution step, gated behind `warranty.approve-refund` at the route level (not `warranty.resolve`). */
final class ApproveRefundHandler
{
    public function __construct(
        private readonly WarrantyClaimRepository $claims,
        private readonly PaymentRefundService $refunds,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(ApproveRefundCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $id = new WarrantyClaimId($command->warrantyClaimId);
            $claim = $this->claims->lockForUpdate($id);
            $before = ['resolution_state' => $claim->resolutionState()];

            $this->refunds->refund(new RefundPaymentCommand(
                transactionId: $command->paymentTransactionId,
                refundedByStaffId: $command->approvedByStaffId,
            ));

            $claim->resolve($command->paymentTransactionId);
            $this->claims->save($claim);

            $this->audit->record(
                module: 'Warranty',
                eventType: 'WarrantyClaimRefundApproved',
                actorStaffId: new StaffId($command->approvedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'warranty_claim',
                subjectId: $id->value,
                beforeState: $before,
                afterState: ['resolution_state' => $claim->resolutionState(), 'payment_transaction_id' => $command->paymentTransactionId],
            );

            Event::dispatch(new WarrantyClaimResolved($id->value, 'refund', CarbonImmutable::now()));
        });
    }
}
