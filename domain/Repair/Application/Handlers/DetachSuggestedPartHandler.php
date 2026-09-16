<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Repair\Application\Commands\DetachSuggestedPartCommand;
use Domain\Repair\Domain\Repositories\DeviceProblemSuggestedPartRepository;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class DetachSuggestedPartHandler
{
    public function __construct(
        private readonly DeviceProblemSuggestedPartRepository $suggestedParts,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(DetachSuggestedPartCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $this->suggestedParts->detach($command->suggestedPartId);

            $this->audit->record(
                module: 'Repair',
                eventType: 'DeviceProblemSuggestedPartDetached',
                actorStaffId: new StaffId($command->detachedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'device_problem_suggested_sku',
                subjectId: $command->suggestedPartId,
                beforeState: null,
                afterState: null,
            );
        });
    }
}
