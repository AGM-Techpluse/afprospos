<?php

declare(strict_types=1);

namespace Domain\Warranty\Domain\Entities;

use Domain\Warranty\Domain\Exceptions\InvalidWarrantyClaimTransition;
use Domain\Warranty\Domain\Policies\WarrantyClaimTransitionPolicy;
use Domain\Warranty\Domain\ValueObjects\WarrantyClaimId;

/** Mutable aggregate (DBDD §15.2), mirrors CollectionCase's shape. */
final class WarrantyClaim
{
    private function __construct(
        private readonly ?WarrantyClaimId $id,
        private readonly int $warrantyPolicyId,
        private readonly ?int $originatingSaleId,
        private readonly ?int $originatingRepairJobId,
        private readonly int $customerId,
        private readonly ?int $inventoryItemId,
        private string $resolutionState,
        private ?string $assessmentNotes,
        private ?string $selectedRemedy,
        private ?int $remedyReferenceId,
    ) {}

    public static function submit(
        int $warrantyPolicyId,
        ?int $originatingSaleId,
        ?int $originatingRepairJobId,
        int $customerId,
        ?int $inventoryItemId,
    ): self {
        return new self(null, $warrantyPolicyId, $originatingSaleId, $originatingRepairJobId, $customerId, $inventoryItemId, 'submitted', null, null, null);
    }

    public static function reconstitute(
        WarrantyClaimId $id,
        int $warrantyPolicyId,
        ?int $originatingSaleId,
        ?int $originatingRepairJobId,
        int $customerId,
        ?int $inventoryItemId,
        string $resolutionState,
        ?string $assessmentNotes,
        ?string $selectedRemedy,
        ?int $remedyReferenceId,
    ): self {
        return new self($id, $warrantyPolicyId, $originatingSaleId, $originatingRepairJobId, $customerId, $inventoryItemId, $resolutionState, $assessmentNotes, $selectedRemedy, $remedyReferenceId);
    }

    /** WAR-BR-04: one staff decision records both the eligibility outcome and the notes it was based on — there's no separately-tracked "began assessing" step in this flow. */
    public function assess(bool $eligible, ?string $notes): void
    {
        $target = $eligible ? 'eligible' : 'not_eligible';
        $this->assertTransition($target);
        $this->resolutionState = $target;
        $this->assessmentNotes = $notes;
    }

    /** WAR-BR-09: selecting `refund` only records the request — it does not move money. Execution is a separate step (`resolve()`/ApproveRefundCommand). */
    public function selectRemedy(string $remedy): void
    {
        $this->assertTransition('remedy_selected');
        $this->resolutionState = 'remedy_selected';
        $this->selectedRemedy = $remedy;
    }

    public function resolve(int $remedyReferenceId): void
    {
        $this->assertTransition('resolved');
        $this->resolutionState = 'resolved';
        $this->remedyReferenceId = $remedyReferenceId;
    }

    private function assertTransition(string $to): void
    {
        if (! (new WarrantyClaimTransitionPolicy)->canTransition($this->resolutionState, $to)) {
            throw InvalidWarrantyClaimTransition::forClaim($this->id?->value ?? 0, $this->resolutionState, $to);
        }
    }

    public function id(): ?WarrantyClaimId
    {
        return $this->id;
    }

    public function warrantyPolicyId(): int
    {
        return $this->warrantyPolicyId;
    }

    public function originatingSaleId(): ?int
    {
        return $this->originatingSaleId;
    }

    public function originatingRepairJobId(): ?int
    {
        return $this->originatingRepairJobId;
    }

    public function customerId(): int
    {
        return $this->customerId;
    }

    public function inventoryItemId(): ?int
    {
        return $this->inventoryItemId;
    }

    public function resolutionState(): string
    {
        return $this->resolutionState;
    }

    public function assessmentNotes(): ?string
    {
        return $this->assessmentNotes;
    }

    public function selectedRemedy(): ?string
    {
        return $this->selectedRemedy;
    }

    public function remedyReferenceId(): ?int
    {
        return $this->remedyReferenceId;
    }
}
