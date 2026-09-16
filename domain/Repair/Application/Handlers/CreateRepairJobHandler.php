<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use App\Support\Transactions\Atomic;
use Carbon\CarbonImmutable;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Repair\Application\Commands\CreateRepairJobCommand;
use Domain\Repair\Domain\Entities\RepairJob;
use Domain\Repair\Domain\Events\RepairCreated;
use Domain\Repair\Domain\Repositories\DeviceProblemTagRepository;
use Domain\Repair\Domain\Repositories\RepairJobProblemTagRepository;
use Domain\Repair\Domain\Repositories\RepairJobRepository;
use Domain\Repair\Domain\ValueObjects\DeviceProblemTagId;
use Domain\Shared\Domain\ValueObjects\CustomerId;
use Domain\Shared\Domain\ValueObjects\Money;
use Domain\Shared\Domain\ValueObjects\ShopId;
use Domain\Shared\Domain\ValueObjects\StaffId;
use Illuminate\Support\Facades\Event;

final class CreateRepairJobHandler
{
    public function __construct(
        private readonly RepairJobRepository $repairJobs,
        private readonly DeviceProblemTagRepository $problemTags,
        private readonly RepairJobProblemTagRepository $jobProblemTags,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(CreateRepairJobCommand $command): int
    {
        return $this->atomic->run(function () use ($command): int {
            $job = RepairJob::intake(
                new ShopId($command->shopId),
                new CustomerId($command->customerId),
                $command->deviceMake,
                $command->deviceModel,
                new Money($command->labourChargeMinor),
                $command->reportedIssue,
                $command->deviceImeiSerial,
                $command->deviceLockType,
                $command->deviceLockValue,
            );

            $id = $this->repairJobs->save($job);

            foreach ($command->problemTagIds as $problemTagId) {
                $tag = $this->problemTags->get(new DeviceProblemTagId($problemTagId));
                $this->jobProblemTags->attach($id, $problemTagId, $tag->label());
            }

            $this->audit->record(
                module: 'Repair',
                eventType: 'RepairJobCreated',
                actorStaffId: new StaffId($command->createdByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'repair_job',
                subjectId: $id->value,
                beforeState: null,
                afterState: [
                    'shop_id' => $command->shopId,
                    'customer_id' => $command->customerId,
                    'device_make' => $command->deviceMake,
                    'device_model' => $command->deviceModel,
                    'reported_issue' => $command->reportedIssue,
                    'device_imei_serial' => $command->deviceImeiSerial,
                    // device_lock_value deliberately excluded — a device passcode/pattern never belongs in an audit log, even encrypted at rest elsewhere.
                    'device_lock_type' => $command->deviceLockType,
                    'problem_tag_ids' => $command->problemTagIds,
                    'labour_charge_minor' => $command->labourChargeMinor,
                ],
            );

            Event::dispatch(new RepairCreated($id->value, $command->shopId, $command->customerId, CarbonImmutable::now()));

            return $id->value;
        });
    }
}
