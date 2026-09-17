<?php

declare(strict_types=1);

namespace Domain\Warranty\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Shared\Domain\ValueObjects\StaffId;
use Domain\Warranty\Application\Commands\UpdateWarrantyPolicyCommand;
use Domain\Warranty\Domain\Repositories\WarrantyPolicyRepository;
use Domain\Warranty\Domain\ValueObjects\WarrantyPolicyId;

final class UpdateWarrantyPolicyHandler
{
    public function __construct(
        private readonly WarrantyPolicyRepository $policies,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(UpdateWarrantyPolicyCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $id = new WarrantyPolicyId($command->warrantyPolicyId);
            $policy = $this->policies->get($id);
            $before = ['name' => $policy->name(), 'coverage_duration_days' => $policy->coverageDurationDays()];

            $policy->update(
                $command->name,
                $command->coverageDurationDays,
                $command->coverageStartPoint,
                $command->coveredScope,
                $command->exclusions,
                $command->availableRemedies,
                $command->coverageExtent,
            );
            $this->policies->save($policy);

            $this->audit->record(
                module: 'Warranty',
                eventType: 'WarrantyPolicyUpdated',
                actorStaffId: new StaffId($command->updatedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'warranty_policy',
                subjectId: $id->value,
                beforeState: $before,
                afterState: ['name' => $command->name, 'coverage_duration_days' => $command->coverageDurationDays],
            );
        });
    }
}
