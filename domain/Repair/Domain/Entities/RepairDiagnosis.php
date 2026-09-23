<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Entities;

use Domain\Repair\Domain\ValueObjects\RepairDiagnosisId;
use Domain\Repair\Domain\ValueObjects\RepairJobId;
use Domain\Shared\Domain\ValueObjects\StaffId;

/**
 * A single component test observation (REP-BR-29's "test common
 * components" step) — never the diagnosis itself; see
 * `DiagnosisConclusion` for the root-cause/outcome fact. Immutable once
 * recorded (DBDD §11.2 prose) — no mutators, matching Sale's "no
 * updated_at signals immutable" convention.
 */
final class RepairDiagnosis
{
    private function __construct(
        private readonly ?RepairDiagnosisId $id,
        private readonly RepairJobId $repairJobId,
        private readonly string $component,
        private readonly string $condition,
        private readonly ?string $notes,
        private readonly StaffId $diagnosedByStaffId,
    ) {}

    public static function record(
        RepairJobId $repairJobId,
        string $component,
        string $condition,
        ?string $notes,
        StaffId $diagnosedByStaffId,
    ): self {
        return new self(null, $repairJobId, $component, $condition, $notes, $diagnosedByStaffId);
    }

    public static function reconstitute(
        RepairDiagnosisId $id,
        RepairJobId $repairJobId,
        string $component,
        string $condition,
        ?string $notes,
        StaffId $diagnosedByStaffId,
    ): self {
        return new self($id, $repairJobId, $component, $condition, $notes, $diagnosedByStaffId);
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

    public function diagnosedByStaffId(): StaffId
    {
        return $this->diagnosedByStaffId;
    }
}
