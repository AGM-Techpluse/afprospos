<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Repair\Application\Commands\UpdateDeviceProblemTagCommand;
use Domain\Repair\Domain\Repositories\DeviceProblemTagRepository;
use Domain\Repair\Domain\ValueObjects\DeviceProblemTagId;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class UpdateDeviceProblemTagHandler
{
    public function __construct(
        private readonly DeviceProblemTagRepository $problemTags,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(UpdateDeviceProblemTagCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $id = new DeviceProblemTagId($command->deviceProblemTagId);
            $tag = $this->problemTags->get($id);
            $before = ['label' => $tag->label()];

            $tag->rename($command->label);
            $tag->reorder($command->sortOrder);
            $this->problemTags->save($tag);

            $this->audit->record(
                module: 'Repair',
                eventType: 'DeviceProblemTagUpdated',
                actorStaffId: new StaffId($command->updatedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'device_problem_tag',
                subjectId: $id->value,
                beforeState: $before,
                afterState: ['label' => $command->label],
            );
        });
    }
}
