<?php

declare(strict_types=1);

namespace Domain\Payments\Domain\Entities;

use DateTimeImmutable;
use Domain\Payments\Domain\Exceptions\InvalidPaymentStateTransition;
use Domain\Payments\Domain\Exceptions\PaymentAlreadyConfirmed;
use Domain\Payments\Domain\Policies\PaymentTransitionPolicy;
use Domain\Payments\Domain\ValueObjects\PayableType;
use Domain\Payments\Domain\ValueObjects\PaymentMethod;
use Domain\Payments\Domain\ValueObjects\PaymentTransactionId;
use Domain\Shared\Domain\ValueObjects\Money;
use Domain\Shared\Domain\ValueObjects\StaffId;
use InvalidArgumentException;

/**
 * The payment ledger's aggregate (DBDD §16) — mutable, like SalesCheckout,
 * because it genuinely evolves through a real lifecycle (pending ->
 * payment_pending_confirmation/confirmed -> disputed/refunded/exception).
 * Every mutator delegates transition legality to PaymentTransitionPolicy;
 * `confirm()` is the one method with its own idempotency guard
 * (PaymentAlreadyConfirmed) rather than a hard InvalidPaymentStateTransition,
 * per DBDD §16.2's duplicate-callback requirement.
 */
final class PaymentTransaction
{
    private function __construct(
        private readonly ?PaymentTransactionId $id,
        private readonly PayableType $payableType,
        private readonly int $payableId,
        private readonly PaymentMethod $method,
        private readonly Money $amount,
        private string $status,
        private ?string $providerReference,
        private ?StaffId $confirmedByStaffId,
        private ?DateTimeImmutable $disputeOpenedAt,
        private ?string $disputeProofReference,
        private ?StaffId $disputeResolvedByStaffId,
    ) {}

    public static function initiate(
        PayableType $payableType,
        int $payableId,
        PaymentMethod $method,
        Money $amount,
        ?string $providerReference = null,
    ): self {
        return new self(null, $payableType, $payableId, $method, $amount, 'pending', $providerReference, null, null, null, null);
    }

    public static function reconstitute(
        PaymentTransactionId $id,
        PayableType $payableType,
        int $payableId,
        PaymentMethod $method,
        Money $amount,
        string $status,
        ?string $providerReference,
        ?StaffId $confirmedByStaffId,
        ?DateTimeImmutable $disputeOpenedAt,
        ?string $disputeProofReference,
        ?StaffId $disputeResolvedByStaffId,
    ): self {
        return new self($id, $payableType, $payableId, $method, $amount, $status, $providerReference, $confirmedByStaffId, $disputeOpenedAt, $disputeProofReference, $disputeResolvedByStaffId);
    }

    public function markPendingConfirmation(?string $providerReference): void
    {
        $this->assertTransition('payment_pending_confirmation');

        if ($providerReference !== null) {
            $this->providerReference = $providerReference;
        }

        $this->status = 'payment_pending_confirmation';
    }

    public function confirm(StaffId $confirmedByStaffId, ?string $providerReference = null): void
    {
        if ($this->status === 'confirmed') {
            throw PaymentAlreadyConfirmed::forTransaction($this->id?->value ?? 0);
        }

        $this->assertTransition('confirmed');

        if ($providerReference !== null) {
            $this->providerReference = $providerReference;
        }

        $this->confirmedByStaffId = $confirmedByStaffId;
        $this->status = 'confirmed';
    }

    public function reject(): void
    {
        $this->assertTransition('exception');
        $this->status = 'exception';
    }

    public function openDispute(?string $proofReference, DateTimeImmutable $now): void
    {
        $this->assertTransition('disputed');
        $this->disputeOpenedAt = $now;
        $this->disputeProofReference = $proofReference;
        $this->status = 'disputed';
    }

    /** @param  'confirmed'|'refunded'|'exception'  $resolution */
    public function resolveDispute(StaffId $resolvedBy, string $resolution): void
    {
        if (! in_array($resolution, ['confirmed', 'refunded', 'exception'], true)) {
            throw new InvalidArgumentException("Invalid dispute resolution [{$resolution}].");
        }

        $this->assertTransition($resolution);
        $this->disputeResolvedByStaffId = $resolvedBy;

        if ($resolution === 'confirmed') {
            $this->confirmedByStaffId = $resolvedBy;
        }

        $this->status = $resolution;
    }

    public function refund(): void
    {
        $this->assertTransition('refunded');
        $this->status = 'refunded';
    }

    private function assertTransition(string $to): void
    {
        if (! (new PaymentTransitionPolicy)->canTransition($this->status, $to)) {
            throw InvalidPaymentStateTransition::forTransaction($this->id?->value ?? 0, $this->status, $to);
        }
    }

    public function id(): ?PaymentTransactionId
    {
        return $this->id;
    }

    public function payableType(): PayableType
    {
        return $this->payableType;
    }

    public function payableId(): int
    {
        return $this->payableId;
    }

    public function method(): PaymentMethod
    {
        return $this->method;
    }

    public function amount(): Money
    {
        return $this->amount;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function providerReference(): ?string
    {
        return $this->providerReference;
    }

    public function confirmedByStaffId(): ?StaffId
    {
        return $this->confirmedByStaffId;
    }

    public function disputeOpenedAt(): ?DateTimeImmutable
    {
        return $this->disputeOpenedAt;
    }

    public function disputeProofReference(): ?string
    {
        return $this->disputeProofReference;
    }

    public function disputeResolvedByStaffId(): ?StaffId
    {
        return $this->disputeResolvedByStaffId;
    }
}
