<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Payments;

use Domain\Payments\Domain\Entities\PaymentTransaction;
use Domain\Payments\Domain\Exceptions\InvalidPaymentStateTransition;
use Domain\Payments\Domain\Exceptions\PaymentAlreadyConfirmed;
use Domain\Payments\Domain\ValueObjects\PayableType;
use Domain\Payments\Domain\ValueObjects\PaymentMethod;
use Domain\Shared\Domain\ValueObjects\Money;
use Domain\Shared\Domain\ValueObjects\StaffId;
use Tests\TestCase;

class PaymentTransactionTest extends TestCase
{
    public function test_cash_payment_can_be_confirmed(): void
    {
        $transaction = PaymentTransaction::initiate(
            new PayableType('sales_checkout'),
            1,
            new PaymentMethod('cash'),
            new Money(5000),
        );

        $transaction->confirm(new StaffId(1));

        $this->assertSame('confirmed', $transaction->status());
        $this->assertTrue($transaction->confirmedByStaffId()->equals(new StaffId(1)));
    }

    public function test_confirming_an_already_confirmed_transaction_is_idempotent_not_an_error(): void
    {
        $transaction = PaymentTransaction::initiate(
            new PayableType('sales_checkout'),
            1,
            new PaymentMethod('cash'),
            new Money(5000),
        );
        $transaction->confirm(new StaffId(1));

        $this->expectException(PaymentAlreadyConfirmed::class);
        $transaction->confirm(new StaffId(2));
    }

    public function test_a_refunded_transaction_cannot_be_confirmed_again(): void
    {
        $transaction = PaymentTransaction::initiate(
            new PayableType('sales_checkout'),
            1,
            new PaymentMethod('cash'),
            new Money(5000),
        );
        $transaction->confirm(new StaffId(1));
        $transaction->refund();

        $this->expectException(InvalidPaymentStateTransition::class);
        $transaction->confirm(new StaffId(1));
    }

    public function test_a_pending_transaction_cannot_be_disputed(): void
    {
        $transaction = PaymentTransaction::initiate(
            new PayableType('sales_checkout'),
            1,
            new PaymentMethod('bank_transfer'),
            new Money(5000),
        );

        $this->expectException(InvalidPaymentStateTransition::class);
        $transaction->openDispute(null, new \DateTimeImmutable);
    }

    public function test_resolving_a_dispute_as_confirmed_records_the_resolver_as_confirmed_by(): void
    {
        $transaction = PaymentTransaction::initiate(
            new PayableType('sales_checkout'),
            1,
            new PaymentMethod('bank_transfer'),
            new Money(5000),
        );
        $transaction->markPendingConfirmation(null);
        $transaction->openDispute('proof.pdf', new \DateTimeImmutable);

        $resolver = new StaffId(9);
        $transaction->resolveDispute($resolver, 'confirmed');

        $this->assertSame('confirmed', $transaction->status());
        $this->assertTrue($transaction->confirmedByStaffId()->equals($resolver));
        $this->assertTrue($transaction->disputeResolvedByStaffId()->equals($resolver));
    }
}
