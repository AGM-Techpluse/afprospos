<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Entities;

use Domain\Repair\Domain\ValueObjects\DiagnosisConclusionId;
use Domain\Repair\Domain\ValueObjects\RepairJobId;
use Domain\Shared\Domain\ValueObjects\StaffId;

/**
 * REP-BR-29: the technician's actual diagnosis — a stated root cause and
 * a repairable/unrepairable/requires-further-assessment classification —
 * distinct from the per-component test observations recorded via
 * `RepairDiagnosis`. Immutable once recorded, same convention as
 * `RepairDiagnosis`.
 */
final class DiagnosisConclusion
{
    private function __construct(
        private readonly ?DiagnosisConclusionId $id,
        private readonly RepairJobId $repairJobId,
        private readonly string $rootCause,
        private readonly string $outcome,
        private readonly StaffId $diagnosedByStaffId,
    ) {}

    /** @param  'repairable'|'unrepairable'|'requires_further_assessment'  $outcome */
    public static function record(
        RepairJobId $repairJobId,
        string $rootCause,
        string $outcome,
        StaffId $diagnosedByStaffId,
    ): self {
        return new self(null, $repairJobId, $rootCause, $outcome, $diagnosedByStaffId);
    }

    public static function reconstitute(
        DiagnosisConclusionId $id,
        RepairJobId $repairJobId,
        string $rootCause,
        string $outcome,
        StaffId $diagnosedByStaffId,
    ): self {
        return new self($id, $repairJobId, $rootCause, $outcome, $diagnosedByStaffId);
    }

    public function id(): ?DiagnosisConclusionId
    {
        return $this->id;
    }

    public function repairJobId(): RepairJobId
    {
        return $this->repairJobId;
    }

    public function rootCause(): string
    {
        return $this->rootCause;
    }

    public function outcome(): string
    {
        return $this->outcome;
    }

    public function diagnosedByStaffId(): StaffId
    {
        return $this->diagnosedByStaffId;
    }
}
