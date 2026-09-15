<?php

declare(strict_types=1);

namespace Domain\Payments\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Payments\Application\Commands\ConfirmPaymentCommand;
use Domain\Payments\Domain\Exceptions\PaymentAlreadyConfirmed;
use Domain\Payments\Domain\Repositories\PaymentTransactionRepository;
use Domain\Payments\Domain\ValueObjects\PaymentTransactionId;
use Domain\Shared\Domain\ValueObjects\StaffId;

/** DBDD §16.2: a duplicate confirm call must never fail or downgrade an already-confirmed payment — it's audited and ignored, not re-thrown. */
final class ConfirmPaymentHandler
{
    public function __construct(
        private readonly PaymentTransactionRepository $transactions,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(ConfirmPaymentCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $id = new PaymentTransactionId($command->transactionId);
            $transaction = $this->transactions->lockForUpdate($id);
            $actor = new StaffId($command->confirmedByStaffId);

            try {
                $transaction->confirm($actor, $command->providerReference);
            } catch (PaymentAlreadyConfirmed) {
                $this->audit->record(
                    module: 'Payments',
                    eventType: 'PaymentConfirmDuplicateIgnored',
                    actorStaffId: $actor,
                    actorRoleSnapshot: null,
                    subjectType: 'payments_transaction',
                    subjectId: $id->value,
                    beforeState: null,
                    afterState: null,
                );

                return;
            }

            $this->transactions->save($transaction);

            $this->audit->record(
                module: 'Payments',
                eventType: 'PaymentConfirmed',
                actorStaffId: $actor,
                actorRoleSnapshot: null,
                subjectType: 'payments_transaction',
                subjectId: $id->value,
                beforeState: null,
                afterState: ['provider_reference' => $transaction->providerReference()],
            );
        });
    }
}
