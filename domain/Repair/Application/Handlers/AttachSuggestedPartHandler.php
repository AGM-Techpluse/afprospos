<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Repair\Application\Commands\AttachSuggestedPartCommand;
use Domain\Repair\Domain\Repositories\DeviceProblemSuggestedPartRepository;
use Domain\Repair\Domain\ValueObjects\DeviceProblemTagId;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class AttachSuggestedPartHandler
{
    public function __construct(
        private readonly DeviceProblemSuggestedPartRepository $suggestedParts,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(AttachSuggestedPartCommand $command): int
    {
        return $this->atomic->run(function () use ($command): int {
            $id = $this->suggestedParts->attach(new DeviceProblemTagId($command->deviceProblemTagId), $command->skuId);

            $this->audit->record(
                module: 'Repair',
                eventType: 'DeviceProblemSuggestedPartAttached',
                actorStaffId: new StaffId($command->attachedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'device_problem_tag',
                subjectId: $command->deviceProblemTagId,
                beforeState: null,
                afterState: ['sku_id' => $command->skuId],
            );

            return $id;
        });
    }
}
