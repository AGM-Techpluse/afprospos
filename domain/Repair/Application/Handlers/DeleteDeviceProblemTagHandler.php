<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Repair\Application\Commands\DeleteDeviceProblemTagCommand;
use Domain\Repair\Domain\Repositories\DeviceProblemTagRepository;
use Domain\Repair\Domain\ValueObjects\DeviceProblemTagId;
use Domain\Shared\Domain\ValueObjects\StaffId;

/** Deleting a problem tag cascades to its suggested-part rows; any repair job that already selected it keeps its label_snapshot (migration's nullOnDelete). */
final class DeleteDeviceProblemTagHandler
{
    public function __construct(
        private readonly DeviceProblemTagRepository $problemTags,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(DeleteDeviceProblemTagCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $id = new DeviceProblemTagId($command->deviceProblemTagId);
            $tag = $this->problemTags->get($id);

            $this->problemTags->delete($id);

            $this->audit->record(
                module: 'Repair',
                eventType: 'DeviceProblemTagDeleted',
                actorStaffId: new StaffId($command->deletedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'device_problem_tag',
                subjectId: $id->value,
                beforeState: ['label' => $tag->label()],
                afterState: null,
            );
        });
    }
}
