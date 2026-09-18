<?php

declare(strict_types=1);

namespace Domain\Warranty\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Payments\Application\Commands\RefundPaymentCommand;
use Domain\Payments\Application\Contracts\PaymentRefundService;
use Domain\Shared\Domain\ValueObjects\StaffId;
use Domain\Warranty\Application\Commands\ProcessReturnRefundCommand;
use Domain\Warranty\Domain\Repositories\ReturnRequestRepository;
use Domain\Warranty\Domain\ValueObjects\ReturnRequestId;

/** Separation of duties (mirrors Claims' ApproveRefundHandler): approve/deny only records the decision, this is the separate step that actually moves money — gated behind `warranty.approve-refund` at the route level. */
final class ProcessReturnRefundHandler
{
    public function __construct(
        private readonly ReturnRequestRepository $returns,
        private readonly PaymentRefundService $refunds,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(ProcessReturnRefundCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $id = new ReturnRequestId($command->returnRequestId);
            $returnRequest = $this->returns->lockForUpdate($id);
            $before = ['resolution_state' => $returnRequest->resolutionState()];

            $this->refunds->refund(new RefundPaymentCommand(
                transactionId: $command->paymentTransactionId,
                refundedByStaffId: $command->processedByStaffId,
            ));

            $returnRequest->resolve($command->paymentTransactionId);
            $this->returns->save($returnRequest);

            $this->audit->record(
                module: 'Warranty',
                eventType: 'ReturnResolved',
                actorStaffId: new StaffId($command->processedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'return_request',
                subjectId: $id->value,
                beforeState: $before,
                afterState: ['resolution_state' => $returnRequest->resolutionState(), 'refund_transaction_id' => $command->paymentTransactionId],
            );
        });
    }
}
