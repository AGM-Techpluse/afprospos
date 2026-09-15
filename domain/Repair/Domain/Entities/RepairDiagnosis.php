<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Entities;

use Domain\Repair\Domain\ValueObjects\RepairDiagnosisId;
use Domain\Repair\Domain\ValueObjects\RepairJobId;
use Domain\Shared\Domain\ValueObjects\StaffId;

/** Immutable once recorded (DBDD §11.2 prose) — no mutators, matching Sale's "no updated_at signals immutable" convention. */
final class RepairDiagnosis
{
    private function __construct(
        private readonly ?RepairDiagnosisId $id,
        private readonly RepairJobId $repairJobId,
        private readonly string $component,
        private readonly string $condition,
        private readonly ?string $notes,
        private readonly ?string $outcome,
        private readonly StaffId $diagnosedByStaffId,
    ) {}

    public static function record(
        RepairJobId $repairJobId,
        string $component,
        string $condition,
        ?string $notes,
        ?string $outcome,
        StaffId $diagnosedByStaffId,
    ): self {
        return new self(null, $repairJobId, $component, $condition, $notes, $outcome, $diagnosedByStaffId);
    }

    public static function reconstitute(
        RepairDiagnosisId $id,
        RepairJobId $repairJobId,
        string $component,
        string $condition,
        ?string $notes,
        ?string $outcome,
        StaffId $diagnosedByStaffId,
    ): self {
        return new self($id, $repairJobId, $component, $condition, $notes, $outcome, $diagnosedByStaffId);
    }

    public function id(): ?RepairDiagnosisId
    {
        return $this->id;
    }

    public function repairJobId(): RepairJobId
    {
        return $this->repairJobId;
    }

    public function component(): string
    {
        return $this->component;
    }

    public function condition(): string
    {
        return $this->condition;
    }

    public function notes(): ?string
    {
        return $this->notes;
    }

    public function outcome(): ?string
    {
        return $this->outcome;
    }

    public function diagnosedByStaffId(): StaffId
    {
        return $this->diagnosedByStaffId;
    }
}
