<?php

declare(strict_types=1);

namespace Domain\Payments\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Payments\Application\Commands\RefundPaymentCommand;
use Domain\Payments\Application\Contracts\PaymentGatewayResolver;
use Domain\Payments\Application\DTOs\RefundPaymentGatewayRequest;
use Domain\Payments\Domain\Repositories\PaymentTransactionRepository;
use Domain\Payments\Domain\ValueObjects\PaymentTransactionId;
use Domain\Shared\Domain\ValueObjects\StaffId;

/** Calls the gateway's refund() uniformly regardless of method (a no-op for manual gateways today) — keeps this Handler branch-free and matches how a real provider's refund will need to work once one exists. */
final class RefundPaymentHandler
{
    public function __construct(
        private readonly PaymentTransactionRepository $transactions,
        private readonly PaymentGatewayResolver $gateways,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(RefundPaymentCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $id = new PaymentTransactionId($command->transactionId);
            $transaction = $this->transactions->lockForUpdate($id);
            $actor = new StaffId($command->refundedByStaffId);

            $gateway = $this->gateways->resolve($transaction->method());
            $gatewayResult = $gateway->refund(new RefundPaymentGatewayRequest(
                method: $transaction->method()->value,
                amountMinor: $transaction->amount()->minor,
                providerReference: $transaction->providerReference(),
            ));

            $transaction->refund();
            $this->transactions->save($transaction);

            $this->audit->record(
                module: 'Payments',
                eventType: 'PaymentRefunded',
                actorStaffId: $actor,
                actorRoleSnapshot: null,
                subjectType: 'payments_transaction',
                subjectId: $id->value,
                beforeState: null,
                afterState: ['provider_reference' => $gatewayResult->providerReference],
            );
        });
    }
}
