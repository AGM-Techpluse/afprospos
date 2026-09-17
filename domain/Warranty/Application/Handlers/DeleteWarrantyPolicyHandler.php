<?php

declare(strict_types=1);

namespace Domain\Warranty\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Shared\Domain\ValueObjects\StaffId;
use Domain\Warranty\Application\Commands\DeleteWarrantyPolicyCommand;
use Domain\Warranty\Domain\Repositories\WarrantyPolicyRepository;
use Domain\Warranty\Domain\ValueObjects\WarrantyPolicyId;

/** DB `restrictOnDelete()` (warranty_claims.warranty_policy_id) surfaces as a friendly validation error at the Controller if the policy is still in use — not caught here, this Handler is the one write path (CPNC §2.4). */
final class DeleteWarrantyPolicyHandler
{
    public function __construct(
        private readonly WarrantyPolicyRepository $policies,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(DeleteWarrantyPolicyCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $id = new WarrantyPolicyId($command->warrantyPolicyId);
            $policy = $this->policies->get($id);

            $this->policies->delete($id);

            $this->audit->record(
                module: 'Warranty',
                eventType: 'WarrantyPolicyDeleted',
                actorStaffId: new StaffId($command->deletedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'warranty_policy',
                subjectId: $id->value,
                beforeState: ['name' => $policy->name()],
                afterState: null,
            );
        });
    }
}
