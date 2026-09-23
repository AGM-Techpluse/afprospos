<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Commands;

/** REP-BR-29: the technician's actual diagnosis — root cause plus a repairable/unrepairable/requires-further-assessment classification — distinct from per-component test observations. */
final readonly class RecordDiagnosisConclusionCommand
{
    /** @param  'repairable'|'unrepairable'|'requires_further_assessment'  $outcome */
    public function __construct(
        public int $repairJobId,
        public string $rootCause,
        public string $outcome,
        public int $diagnosedByStaffId,
    ) {}
}
