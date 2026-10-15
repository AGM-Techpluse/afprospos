<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Repair\Application\Commands\AssignDiagnosisTechnicianCommand;
use Domain\Repair\Domain\Exceptions\InvalidDiagnosisCaseTransition;
use Domain\Repair\Infrastructure\Persistence\Eloquent\DiagnosisCaseRecord;
use Domain\Shared\Domain\ValueObjects\StaffId;

/**
 * DiagnosisCase has no pure Domain entity of its own yet (it's a working-notes
 * record, not an aggregate with invariants beyond this one) — same precedent
 * as CompleteRepairHandler operating on RepairFixedFaultRecord directly, but
 * still routed through a proper Handler: locked, guarded, and audited, rather
 * than a bare Eloquent update in the controller.
 */
final class AssignDiagnosisTechnicianHandler
{
    /** Once a repair has been opened from this case (`called_up`) or it was `declined`, the case's own technician field is no longer meaningful — the repair already has its own, independently reassignable technician. */
    private const ASSIGNABLE_STATUSES = ['received', 'diagnosed'];

    public function __construct(
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(AssignDiagnosisTechnicianCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $case = DiagnosisCaseRecord::query()->lockForUpdate()->findOrFail($command->diagnosisCaseId);

            if (! in_array($case->status, self::ASSIGNABLE_STATUSES, true)) {
                throw InvalidDiagnosisCaseTransition::forCase($command->diagnosisCaseId, $case->status);
            }

            $before = $case->diagnosis_technician_staff_id;
            $case->update(['diagnosis_technician_staff_id' => $command->technicianStaffId]);

            $this->audit->record(
                module: 'Repair',
                eventType: 'DiagnosisTechnicianAssigned',
                actorStaffId: new StaffId($command->assignedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'diagnosis_case',
                subjectId: $command->diagnosisCaseId,
                beforeState: ['diagnosis_technician_staff_id' => $before],
                afterState: ['diagnosis_technician_staff_id' => $command->technicianStaffId],
            );
        });
    }
}
