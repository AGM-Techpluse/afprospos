<?php

declare(strict_types=1);

namespace Domain\Payments\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Payments\Application\Commands\InitiatePaymentCommand;
use Domain\Payments\Application\Contracts\PaymentGatewayResolver;
use Domain\Payments\Application\DTOs\InitiatePaymentGatewayRequest;
use Domain\Payments\Application\DTOs\PaymentInitiationResult;
use Domain\Payments\Domain\Entities\PaymentTransaction;
use Domain\Payments\Domain\Repositories\PaymentTransactionRepository;
use Domain\Payments\Domain\ValueObjects\PayableType;
use Domain\Payments\Domain\ValueObjects\PaymentMethod;
use Domain\Shared\Domain\ValueObjects\Money;
use Domain\Shared\Domain\ValueObjects\StaffId;
use LogicException;

/**
 * Creates the transaction and immediately routes it through the resolved
 * gateway: cash/POS-terminal confirm synchronously (nothing external to
 * wait for), bank-transfer moves to `payment_pending_confirmation`. This
 * is what `PaymentInitiationService` (the cross-module contract Sales
 * calls) ultimately delegates to.
 */
final class InitiatePaymentHandler
{
    public function __construct(
        private readonly PaymentTransactionRepository $transactions,
        private readonly PaymentGatewayResolver $gateways,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(InitiatePaymentCommand $command): PaymentInitiationResult
    {
        return $this->atomic->run(function () use ($command): PaymentInitiationResult {
            $method = new PaymentMethod($command->method);
            $gateway = $this->gateways->resolve($method);

            $gatewayResult = $gateway->initiate(new InitiatePaymentGatewayRequest(
                method: $command->method,
                amountMinor: $command->amountMinor,
                providerReference: $command->providerReference,
            ));

            $transaction = PaymentTransaction::initiate(
                new PayableType($command->payableType),
                $command->payableId,
                $method,
                new Money($command->amountMinor),
                $gatewayResult->providerReference,
            );

            if ($gatewayResult->status === 'confirmed') {
                if ($command->initiatedByStaffId === null) {
                    throw new LogicException('A gateway that confirms synchronously requires a staff initiator; customer-initiated methods must resolve to a pending status.');
                }

                $transaction->confirm(new StaffId($command->initiatedByStaffId), $gatewayResult->providerReference);
            } else {
                $transaction->markPendingConfirmation($gatewayResult->providerReference);
            }

            $id = $this->transactions->save($transaction);

            $this->audit->record(
                module: 'Payments',
                eventType: 'PaymentInitiated',
                actorStaffId: $command->initiatedByStaffId !== null ? new StaffId($command->initiatedByStaffId) : null,
                actorRoleSnapshot: null,
                subjectType: 'payments_transaction',
                subjectId: $id->value,
                beforeState: null,
                afterState: [
                    'payable_type' => $command->payableType,
                    'payable_id' => $command->payableId,
                    'method' => $command->method,
                    'amount_minor' => $command->amountMinor,
                    'status' => $transaction->status(),
                ],
                context: $command->initiatedByCustomerId !== null ? ['customer_id' => $command->initiatedByCustomerId] : null,
            );

            return new PaymentInitiationResult($id->value, $transaction->status(), $transaction->providerReference());
        });
    }
}
