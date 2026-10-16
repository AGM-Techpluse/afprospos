<?php

declare(strict_types=1);

namespace Domain\Payments\Domain\Entities;

use Carbon\CarbonImmutable;
use Domain\Payments\Domain\ValueObjects\PaymentRefundId;
use Domain\Payments\Domain\ValueObjects\PaymentTransactionId;
use Domain\Shared\Domain\ValueObjects\Money;
use Domain\Shared\Domain\ValueObjects\StaffId;
use InvalidArgumentException;

/**
 * One append-only row of the refund ledger — never mutated after creation.
 * A transaction's own `amount_minor` is never touched by a refund; this
 * entity is the only record of money actually returned, full or partial,
 * and a transaction may accumulate several of these over time.
 */
final class PaymentRefund
{
    private const SOURCES = ['manual_refund', 'dispute_resolution'];

    private function __construct(
        private readonly ?PaymentRefundId $id,
        private readonly PaymentTransactionId $paymentTransactionId,
        private readonly Money $amount,
        private readonly ?StaffId $refundedByStaffId,
        private readonly string $source,
        private readonly ?CarbonImmutable $createdAt,
    ) {
        if (! in_array($source, self::SOURCES, true)) {
            throw new InvalidArgumentException("Invalid payment refund source [{$source}].");
        }
    }

    /** @param  'manual_refund'|'dispute_resolution'  $source */
    public static function record(PaymentTransactionId $paymentTransactionId, Money $amount, ?StaffId $refundedByStaffId, string $source): self
    {
        return new self(null, $paymentTransactionId, $amount, $refundedByStaffId, $source, null);
    }

    public static function reconstitute(
        PaymentRefundId $id,
        PaymentTransactionId $paymentTransactionId,
        Money $amount,
        ?StaffId $refundedByStaffId,
        string $source,
        CarbonImmutable $createdAt,
    ): self {
        return new self($id, $paymentTransactionId, $amount, $refundedByStaffId, $source, $createdAt);
    }

    public function id(): ?PaymentRefundId
    {
        return $this->id;
    }

    public function paymentTransactionId(): PaymentTransactionId
    {
        return $this->paymentTransactionId;
    }

    public function amount(): Money
    {
        return $this->amount;
    }

    public function refundedByStaffId(): ?StaffId
    {
        return $this->refundedByStaffId;
    }

    public function source(): string
    {
        return $this->source;
    }

    public function createdAt(): ?CarbonImmutable
    {
        return $this->createdAt;
    }
}
