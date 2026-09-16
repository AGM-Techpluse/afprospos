<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Commands;

/**
 * One call per component observation. Passing `outcome` also finalizes
 * the diagnosis session in the same call — Phase 6 scope decision: no
 * separate FinalizeDiagnosisCommand, no synthetic summary row.
 *
 * `component`/`condition` are nullable so a technician who already
 * recorded every observation can finalize with just an `outcome` and no
 * new row — the Handler only writes a RepairDiagnosis when `component`
 * is present; either `component` or `outcome` must be given.
 */
final readonly class RecordDiagnosisCommand
{
    /** @param  'repairable'|'unrepairable'|'requires_further_assessment'|null  $outcome */
    public function __construct(
        public int $repairJobId,
        public ?string $component,
        public ?string $condition,
        public ?string $notes,
        public ?string $outcome,
        public int $diagnosedByStaffId,
    ) {}
}
