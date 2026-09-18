<?php

declare(strict_types=1);

namespace Domain\Warranty\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Shared\Domain\ValueObjects\StaffId;
use Domain\Warranty\Application\Commands\DenyReturnRequestCommand;
use Domain\Warranty\Domain\Repositories\ReturnRequestRepository;
use Domain\Warranty\Domain\ValueObjects\ReturnRequestId;

final class DenyReturnRequestHandler
{
    public function __construct(
        private readonly ReturnRequestRepository $returns,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(DenyReturnRequestCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $id = new ReturnRequestId($command->returnRequestId);
            $returnRequest = $this->returns->lockForUpdate($id);
            $before = ['resolution_state' => $returnRequest->resolutionState()];

            $returnRequest->deny($command->reason);
            $this->returns->save($returnRequest);

            $this->audit->record(
                module: 'Warranty',
                eventType: 'ReturnDenied',
                actorStaffId: new StaffId($command->deniedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'return_request',
                subjectId: $id->value,
                beforeState: $before,
                afterState: ['resolution_state' => $returnRequest->resolutionState(), 'denial_reason' => $command->reason],
            );
        });
    }
}
