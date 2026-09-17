<?php

declare(strict_types=1);

namespace Domain\Warranty\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Shared\Domain\ValueObjects\StaffId;
use Domain\Warranty\Application\Commands\CreateWarrantyPolicyCommand;
use Domain\Warranty\Domain\Entities\WarrantyPolicy;
use Domain\Warranty\Domain\Repositories\WarrantyPolicyRepository;

final class CreateWarrantyPolicyHandler
{
    public function __construct(
        private readonly WarrantyPolicyRepository $policies,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(CreateWarrantyPolicyCommand $command): int
    {
        return $this->atomic->run(function () use ($command): int {
            $policy = WarrantyPolicy::create(
                $command->name,
                $command->coverageDurationDays,
                $command->coverageStartPoint,
                $command->coveredScope,
                $command->exclusions,
                $command->availableRemedies,
                $command->coverageExtent,
            );
            $id = $this->policies->save($policy);

            $this->audit->record(
                module: 'Warranty',
                eventType: 'WarrantyPolicyCreated',
                actorStaffId: new StaffId($command->createdByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'warranty_policy',
                subjectId: $id->value,
                beforeState: null,
                afterState: ['name' => $command->name, 'coverage_duration_days' => $command->coverageDurationDays],
            );

            return $id->value;
        });
    }
}
