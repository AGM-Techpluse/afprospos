<?php

declare(strict_types=1);

namespace Domain\Warranty\Domain\Entities;

use DateTimeImmutable;
use Domain\Warranty\Domain\Exceptions\InvalidReturnRequestTransition;
use Domain\Warranty\Domain\Exceptions\ReturnWindowExpired;
use Domain\Warranty\Domain\Policies\ReturnRequestTransitionPolicy;
use Domain\Warranty\Domain\ValueObjects\ReturnRequestId;

/** Mutable aggregate (DBDD §15.3), mirrors WarrantyClaim's shape. "Return" is a distinct concept from a Warranty Claim (BLD §5.2) — a customer giving back a recently purchased product under the return policy, not reporting a fault. */
final class ReturnRequest
{
    private function __construct(
        private readonly ?ReturnRequestId $id,
        private readonly int $saleId,
        private readonly int $customerId,
        private string $resolutionState,
        private readonly DateTimeImmutable $returnWindowExpiresAt,
        private ?string $denialReason,
        private ?int $overrideApprovedByStaffId,
        private ?int $refundTransactionId,
    ) {}

    public static function request(int $saleId, int $customerId, DateTimeImmutable $returnWindowExpiresAt): self
    {
        return new self(null, $saleId, $customerId, 'requested', $returnWindowExpiresAt, null, null, null);
    }

    public static function reconstitute(
        ReturnRequestId $id,
        int $saleId,
        int $customerId,
        string $resolutionState,
        DateTimeImmutable $returnWindowExpiresAt,
        ?string $denialReason,
        ?int $overrideApprovedByStaffId,
        ?int $refundTransactionId,
    ): self {
        return new self($id, $saleId, $customerId, $resolutionState, $returnWindowExpiresAt, $denialReason, $overrideApprovedByStaffId, $refundTransactionId);
    }

    public function assess(): void
    {
        $this->assertTransition('under_assessment');
        $this->resolutionState = 'under_assessment';
    }

    /** WAR-BR-12: a request made after the return window is denied by default — the normal approve path is blocked once the window has passed; `overrideApprove()` is the explicit administrative override. */
    public function approve(DateTimeImmutable $now): void
    {
        $this->assertTransition('approved');

        if ($now > $this->returnWindowExpiresAt) {
            throw ReturnWindowExpired::forSale($this->saleId);
        }

        $this->resolutionState = 'approved';
    }

    public function deny(string $reason): void
    {
        $this->assertTransition('denied');
        $this->resolutionState = 'denied';
        $this->denialReason = $reason;
    }

    /** WAR-BR-12: the administrative-override path for a request made after the return window (or otherwise denied). */
    public function overrideApprove(int $staffId): void
    {
        $this->assertTransition('approved');
        $this->resolutionState = 'approved';
        $this->overrideApprovedByStaffId = $staffId;
    }

    public function resolve(int $refundTransactionId): void
    {
        $this->assertTransition('resolved');
        $this->resolutionState = 'resolved';
        $this->refundTransactionId = $refundTransactionId;
    }

    private function assertTransition(string $to): void
    {
        if (! (new ReturnRequestTransitionPolicy)->canTransition($this->resolutionState, $to)) {
            throw InvalidReturnRequestTransition::forReturnRequest($this->id?->value ?? 0, $this->resolutionState, $to);
        }
    }

    public function id(): ?ReturnRequestId
    {
        return $this->id;
    }

    public function saleId(): int
    {
        return $this->saleId;
    }

    public function customerId(): int
    {
        return $this->customerId;
    }

    public function resolutionState(): string
    {
        return $this->resolutionState;
    }

    public function returnWindowExpiresAt(): DateTimeImmutable
    {
        return $this->returnWindowExpiresAt;
    }

    public function denialReason(): ?string
    {
        return $this->denialReason;
    }

    public function overrideApprovedByStaffId(): ?int
    {
        return $this->overrideApprovedByStaffId;
    }

    public function refundTransactionId(): ?int
    {
        return $this->refundTransactionId;
    }
}
