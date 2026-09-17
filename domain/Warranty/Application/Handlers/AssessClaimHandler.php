<?php

declare(strict_types=1);

namespace Domain\Warranty\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Shared\Domain\ValueObjects\StaffId;
use Domain\Warranty\Application\Commands\AssessClaimCommand;
use Domain\Warranty\Domain\Repositories\WarrantyClaimRepository;
use Domain\Warranty\Domain\ValueObjects\WarrantyClaimId;

final class AssessClaimHandler
{
    public function __construct(
        private readonly WarrantyClaimRepository $claims,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(AssessClaimCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $id = new WarrantyClaimId($command->warrantyClaimId);
            $claim = $this->claims->lockForUpdate($id);
            $before = ['resolution_state' => $claim->resolutionState()];

            $claim->assess($command->eligible, $command->notes);
            $this->claims->save($claim);

            $this->audit->record(
                module: 'Warranty',
                eventType: 'WarrantyClaimAssessed',
                actorStaffId: new StaffId($command->assessedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'warranty_claim',
                subjectId: $id->value,
                beforeState: $before,
                afterState: ['resolution_state' => $claim->resolutionState(), 'assessment_notes' => $command->notes],
            );
        });
    }
}
