<?php

declare(strict_types=1);

namespace Domain\Payments\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Payments\Application\Commands\RejectPaymentCommand;
use Domain\Payments\Domain\Repositories\PaymentTransactionRepository;
use Domain\Payments\Domain\ValueObjects\PaymentTransactionId;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class RejectPaymentHandler
{
    public function __construct(
        private readonly PaymentTransactionRepository $transactions,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(RejectPaymentCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $id = new PaymentTransactionId($command->transactionId);
            $transaction = $this->transactions->lockForUpdate($id);
            $actor = new StaffId($command->rejectedByStaffId);

            $transaction->reject();
            $this->transactions->save($transaction);

            $this->audit->record(
                module: 'Payments',
                eventType: 'PaymentRejected',
                actorStaffId: $actor,
                actorRoleSnapshot: null,
                subjectType: 'payments_transaction',
                subjectId: $id->value,
                beforeState: null,
                afterState: null,
            );
        });
    }
}
