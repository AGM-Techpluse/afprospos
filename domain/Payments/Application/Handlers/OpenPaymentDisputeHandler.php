<?php

declare(strict_types=1);

namespace Domain\Payments\Application\Handlers;

use App\Support\Transactions\Atomic;
use Carbon\CarbonImmutable;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Payments\Application\Commands\OpenPaymentDisputeCommand;
use Domain\Payments\Domain\Repositories\PaymentTransactionRepository;
use Domain\Payments\Domain\ValueObjects\PaymentTransactionId;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class OpenPaymentDisputeHandler
{
    public function __construct(
        private readonly PaymentTransactionRepository $transactions,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(OpenPaymentDisputeCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $id = new PaymentTransactionId($command->transactionId);
            $transaction = $this->transactions->lockForUpdate($id);
            $actor = new StaffId($command->openedByStaffId);

            $transaction->openDispute($command->disputeProofReference, CarbonImmutable::now());
            $this->transactions->save($transaction);

            $this->audit->record(
                module: 'Payments',
                eventType: 'PaymentDisputeOpened',
                actorStaffId: $actor,
                actorRoleSnapshot: null,
                subjectType: 'payments_transaction',
                subjectId: $id->value,
                beforeState: null,
                afterState: ['dispute_proof_reference' => $command->disputeProofReference],
            );
        });
    }
}
