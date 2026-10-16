<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Payments;

use Domain\Payments\Domain\Entities\PaymentTransaction;
use Domain\Payments\Domain\Exceptions\InvalidPaymentStateTransition;
use Domain\Payments\Domain\Exceptions\PaymentAlreadyConfirmed;
use Domain\Payments\Domain\Exceptions\PaymentRefundExceedsRemainingBalance;
use Domain\Payments\Domain\ValueObjects\PayableType;
use Domain\Payments\Domain\ValueObjects\PaymentMethod;
use Domain\Payments\Domain\ValueObjects\PaymentPurpose;
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
            new PaymentPurpose('sale_payment'),
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
            new PaymentPurpose('sale_payment'),
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
            new PaymentPurpose('sale_payment'),
            new PaymentMethod('cash'),
            new Money(5000),
        );
        $transaction->confirm(new StaffId(1));
        $transaction->applyRefund(0, new Money(5000));

        $this->expectException(InvalidPaymentStateTransition::class);
        $transaction->confirm(new StaffId(1));
    }

    public function test_a_full_refund_in_one_go_moves_the_transaction_to_refunded(): void
    {
        $transaction = PaymentTransaction::initiate(
            new PayableType('sales_checkout'),
            1,
            new PaymentPurpose('sale_payment'),
            new PaymentMethod('cash'),
            new Money(5000),
        );
        $transaction->confirm(new StaffId(1));

        $resultingStatus = $transaction->applyRefund(0, new Money(5000));

        $this->assertSame('refunded', $resultingStatus);
        $this->assertSame('refunded', $transaction->status());
    }

    public function test_a_partial_refund_moves_the_transaction_to_partially_refunded(): void
    {
        $transaction = PaymentTransaction::initiate(
            new PayableType('sales_checkout'),
            1,
            new PaymentPurpose('sale_payment'),
            new PaymentMethod('cash'),
            new Money(5000),
        );
        $transaction->confirm(new StaffId(1));

        $resultingStatus = $transaction->applyRefund(0, new Money(2000));

        $this->assertSame('partially_refunded', $resultingStatus);
        $this->assertSame('partially_refunded', $transaction->status());
    }

    public function test_a_second_partial_refund_that_completes_the_balance_moves_to_refunded(): void
    {
        $transaction = PaymentTransaction::initiate(
            new PayableType('sales_checkout'),
            1,
            new PaymentPurpose('sale_payment'),
            new PaymentMethod('cash'),
            new Money(5000),
        );
        $transaction->confirm(new StaffId(1));
        $transaction->applyRefund(0, new Money(2000));

        $resultingStatus = $transaction->applyRefund(2000, new Money(3000));

        $this->assertSame('refunded', $resultingStatus);
    }

    public function test_a_refund_amount_over_the_remaining_balance_is_rejected(): void
    {
        $transaction = PaymentTransaction::initiate(
            new PayableType('sales_checkout'),
            1,
            new PaymentPurpose('sale_payment'),
            new PaymentMethod('cash'),
            new Money(5000),
        );
        $transaction->confirm(new StaffId(1));
        $transaction->applyRefund(0, new Money(2000));

        $this->expectException(PaymentRefundExceedsRemainingBalance::class);
        $transaction->applyRefund(2000, new Money(4000));
    }

    public function test_a_pending_transaction_cannot_be_disputed(): void
    {
        $transaction = PaymentTransaction::initiate(
            new PayableType('sales_checkout'),
            1,
            new PaymentPurpose('sale_payment'),
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
            new PaymentPurpose('sale_payment'),
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
