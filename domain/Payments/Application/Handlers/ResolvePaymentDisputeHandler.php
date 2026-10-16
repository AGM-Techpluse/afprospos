<?php

declare(strict_types=1);

namespace Domain\Payments\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Payments\Application\Commands\ResolvePaymentDisputeCommand;
use Domain\Payments\Domain\Entities\PaymentRefund;
use Domain\Payments\Domain\Repositories\PaymentRefundRepository;
use Domain\Payments\Domain\Repositories\PaymentTransactionRepository;
use Domain\Payments\Domain\ValueObjects\PaymentTransactionId;
use Domain\Shared\Domain\ValueObjects\Money;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class ResolvePaymentDisputeHandler
{
    public function __construct(
        private readonly PaymentTransactionRepository $transactions,
        private readonly PaymentRefundRepository $refunds,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(ResolvePaymentDisputeCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $id = new PaymentTransactionId($command->transactionId);
            $transaction = $this->transactions->lockForUpdate($id);
            $actor = new StaffId($command->resolvedByStaffId);

            $transaction->resolveDispute($actor, $command->resolution);

            $resultingStatus = $command->resolution;

            if ($command->resolution === 'refunded') {
                $priorRefundedMinor = $this->refunds->totalRefundedMinor($id);
                $refundAmount = new Money($command->amountMinor ?? ($transaction->amount()->minor - $priorRefundedMinor));

                $resultingStatus = $transaction->applyRefund($priorRefundedMinor, $refundAmount);
                $this->refunds->append(PaymentRefund::record($id, $refundAmount, $actor, 'dispute_resolution'));
            }

            $this->transactions->save($transaction);

            $this->audit->record(
                module: 'Payments',
                eventType: 'PaymentDisputeResolved',
                actorStaffId: $actor,
                actorRoleSnapshot: null,
                subjectType: 'payments_transaction',
                subjectId: $id->value,
                beforeState: null,
                afterState: ['resolution' => $command->resolution, 'resulting_status' => $resultingStatus],
            );
        });
    }
}
