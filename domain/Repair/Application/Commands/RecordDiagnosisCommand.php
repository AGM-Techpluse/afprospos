<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Commands;

/**
 * One call per component observation (REP-BR-29's "test common
 * components" step) — the technician's actual diagnosis (root cause +
 * outcome) is a separate, distinct fact recorded via
 * `RecordDiagnosisConclusionCommand`.
 */
final readonly class RecordDiagnosisCommand
{
    public function __construct(
        public int $repairJobId,
        public string $component,
        public string $condition,
        public ?string $notes,
        public int $diagnosedByStaffId,
    ) {}
}
