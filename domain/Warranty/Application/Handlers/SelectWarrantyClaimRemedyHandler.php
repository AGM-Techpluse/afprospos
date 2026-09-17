<?php

declare(strict_types=1);

namespace Domain\Warranty\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Shared\Domain\ValueObjects\StaffId;
use Domain\Warranty\Application\Commands\SelectWarrantyClaimRemedyCommand;
use Domain\Warranty\Domain\Exceptions\UnsupportedWarrantyRemedy;
use Domain\Warranty\Domain\Repositories\WarrantyClaimRepository;
use Domain\Warranty\Domain\ValueObjects\WarrantyClaimId;

final class SelectWarrantyClaimRemedyHandler
{
    private const SUPPORTED_REMEDIES = ['repair', 'replace', 'refund'];

    public function __construct(
        private readonly WarrantyClaimRepository $claims,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(SelectWarrantyClaimRemedyCommand $command): void
    {
        if (! in_array($command->remedy, self::SUPPORTED_REMEDIES, true)) {
            throw UnsupportedWarrantyRemedy::forRemedy($command->remedy);
        }

        $this->atomic->run(function () use ($command): void {
            $id = new WarrantyClaimId($command->warrantyClaimId);
            $claim = $this->claims->lockForUpdate($id);
            $before = ['resolution_state' => $claim->resolutionState()];

            $claim->selectRemedy($command->remedy);
            $this->claims->save($claim);

            $this->audit->record(
                module: 'Warranty',
                eventType: 'WarrantyClaimRemedySelected',
                actorStaffId: new StaffId($command->selectedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'warranty_claim',
                subjectId: $id->value,
                beforeState: $before,
                afterState: ['resolution_state' => $claim->resolutionState(), 'selected_remedy' => $command->remedy],
            );
        });
    }
}
