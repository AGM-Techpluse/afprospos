<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Repair\Application\Commands\CreateDeviceProblemTagCommand;
use Domain\Repair\Domain\Entities\DeviceProblemTag;
use Domain\Repair\Domain\Repositories\DeviceProblemTagRepository;
use Domain\Repair\Domain\ValueObjects\DeviceTypeId;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class CreateDeviceProblemTagHandler
{
    public function __construct(
        private readonly DeviceProblemTagRepository $problemTags,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(CreateDeviceProblemTagCommand $command): int
    {
        return $this->atomic->run(function () use ($command): int {
            $tag = DeviceProblemTag::create(new DeviceTypeId($command->deviceTypeId), $command->label, $command->sortOrder);
            $id = $this->problemTags->save($tag);

            $this->audit->record(
                module: 'Repair',
                eventType: 'DeviceProblemTagCreated',
                actorStaffId: new StaffId($command->createdByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'device_problem_tag',
                subjectId: $id->value,
                beforeState: null,
                afterState: ['device_type_id' => $command->deviceTypeId, 'label' => $command->label],
            );

            return $id->value;
        });
    }
}
