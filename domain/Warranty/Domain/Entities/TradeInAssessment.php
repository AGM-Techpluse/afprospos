<?php

declare(strict_types=1);

namespace Domain\Warranty\Domain\Entities;

use Domain\Warranty\Domain\Exceptions\InvalidTradeInAssessmentTransition;
use Domain\Warranty\Domain\Exceptions\TradeInApproverMustDifferFromAssessor;
use Domain\Warranty\Domain\Policies\TradeInAssessmentTransitionPolicy;
use Domain\Warranty\Domain\ValueObjects\TradeInAssessmentId;

/**
 * Mutable aggregate (DBDD §15.4). BLD §5.7: "A customer's existing device
 * offered toward a new purchase is modeled as an inventory acquisition
 * plus a transaction credit — not as a discount."
 */
final class TradeInAssessment
{
    private function __construct(
        private readonly ?TradeInAssessmentId $id,
        private readonly int $customerId,
        private ?int $relatedCheckoutId,
        private readonly array $deviceDescription,
        private ?int $assessedValueMinor,
        private string $resolutionState,
        private ?int $assessedByStaffId,
        private ?int $approvedByStaffId,
    ) {}

    public static function submit(int $customerId, ?int $relatedCheckoutId, array $deviceDescription): self
    {
        return new self(null, $customerId, $relatedCheckoutId, $deviceDescription, null, 'submitted', null, null);
    }

    public static function reconstitute(
        TradeInAssessmentId $id,
        int $customerId,
        ?int $relatedCheckoutId,
        array $deviceDescription,
        ?int $assessedValueMinor,
        string $resolutionState,
        ?int $assessedByStaffId,
        ?int $approvedByStaffId,
    ): self {
        return new self($id, $customerId, $relatedCheckoutId, $deviceDescription, $assessedValueMinor, $resolutionState, $assessedByStaffId, $approvedByStaffId);
    }

    /** TRADE-BR-01: staff records the assessed trade-in value. */
    public function assess(int $valueMinor, int $staffId): void
    {
        $this->assertTransition('assessed');
        $this->resolutionState = 'assessed';
        $this->assessedValueMinor = $valueMinor;
        $this->assessedByStaffId = $staffId;
    }

    /** TRADE-BR-05: the approver must be a different staff member than whoever assessed the value. */
    public function approve(int $staffId): void
    {
        $this->assertTransition('approved');

        if ($staffId === $this->assessedByStaffId) {
            throw TradeInApproverMustDifferFromAssessor::forTradeIn($this->id?->value ?? 0);
        }

        $this->resolutionState = 'approved';
        $this->approvedByStaffId = $staffId;
    }

    /** No same-staff restriction on rejection — TRADE-BR-05 only guards against a single employee setting AND approving an inflated value. */
    public function reject(int $staffId): void
    {
        $this->assertTransition('rejected');
        $this->resolutionState = 'rejected';
        $this->approvedByStaffId = $staffId;
    }

    /** TRADE-BR-02: the approved value becomes a credit against the customer's purchase transaction once applied to a checkout. */
    public function apply(int $checkoutId): void
    {
        $this->assertTransition('applied');
        $this->resolutionState = 'applied';
        $this->relatedCheckoutId = $checkoutId;
    }

    private function assertTransition(string $to): void
    {
        if (! (new TradeInAssessmentTransitionPolicy)->canTransition($this->resolutionState, $to)) {
            throw InvalidTradeInAssessmentTransition::forTradeIn($this->id?->value ?? 0, $this->resolutionState, $to);
        }
    }

    public function id(): ?TradeInAssessmentId
    {
        return $this->id;
    }

    public function customerId(): int
    {
        return $this->customerId;
    }

    public function relatedCheckoutId(): ?int
    {
        return $this->relatedCheckoutId;
    }

    public function deviceDescription(): array
    {
        return $this->deviceDescription;
    }

    public function assessedValueMinor(): ?int
    {
        return $this->assessedValueMinor;
    }

    public function resolutionState(): string
    {
        return $this->resolutionState;
    }

    public function assessedByStaffId(): ?int
    {
        return $this->assessedByStaffId;
    }

    public function approvedByStaffId(): ?int
    {
        return $this->approvedByStaffId;
    }
}
