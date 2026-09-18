<?php

declare(strict_types=1);

namespace Domain\Warranty\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Shared\Domain\ValueObjects\StaffId;
use Domain\Warranty\Application\Commands\OverrideApproveReturnRequestCommand;
use Domain\Warranty\Domain\Repositories\ReturnRequestRepository;
use Domain\Warranty\Domain\ValueObjects\ReturnRequestId;

/** WAR-BR-12's administrative-override path. */
final class OverrideApproveReturnRequestHandler
{
    public function __construct(
        private readonly ReturnRequestRepository $returns,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(OverrideApproveReturnRequestCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $id = new ReturnRequestId($command->returnRequestId);
            $returnRequest = $this->returns->lockForUpdate($id);
            $before = ['resolution_state' => $returnRequest->resolutionState()];

            $returnRequest->overrideApprove($command->overriddenByStaffId);
            $this->returns->save($returnRequest);

            $this->audit->record(
                module: 'Warranty',
                eventType: 'ReturnApprovedByOverride',
                actorStaffId: new StaffId($command->overriddenByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'return_request',
                subjectId: $id->value,
                beforeState: $before,
                afterState: ['resolution_state' => $returnRequest->resolutionState()],
            );
        });
    }
}
