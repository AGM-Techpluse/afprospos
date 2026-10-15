<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Repair\Application\Commands\UpdateQcFailPolicyCommand;
use Domain\Repair\Application\Queries\QcFailPolicyQuery;
use Domain\Shared\Domain\Contracts\SiteSettings;
use Domain\Shared\Domain\ValueObjects\StaffId;
use InvalidArgumentException;

final class UpdateQcFailPolicyHandler
{
    public function __construct(
        private readonly SiteSettings $settings,
        private readonly QcFailPolicyQuery $policy,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(UpdateQcFailPolicyCommand $command): void
    {
        if (! in_array($command->action, QcFailPolicyQuery::ACTIONS, true)) {
            throw new InvalidArgumentException("Unknown QC fail action [{$command->action}].");
        }

        $this->atomic->run(function () use ($command): void {
            $before = $this->policy->current();

            $this->settings->put(QcFailPolicyQuery::KEY, $command->action, $command->updatedByStaffId);

            $this->audit->record(
                module: 'Repair',
                eventType: 'RepairQcFailPolicyChanged',
                actorStaffId: new StaffId($command->updatedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'site_setting',
                subjectId: 0,
                beforeState: ['qc_fail_action' => $before],
                afterState: ['qc_fail_action' => $command->action],
            );
        });
    }
}
