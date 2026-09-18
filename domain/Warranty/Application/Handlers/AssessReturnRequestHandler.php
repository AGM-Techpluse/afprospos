<?php

declare(strict_types=1);

namespace Domain\Warranty\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Shared\Domain\ValueObjects\StaffId;
use Domain\Warranty\Application\Commands\AssessReturnRequestCommand;
use Domain\Warranty\Domain\Repositories\ReturnRequestRepository;
use Domain\Warranty\Domain\ValueObjects\ReturnRequestId;

final class AssessReturnRequestHandler
{
    public function __construct(
        private readonly ReturnRequestRepository $returns,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(AssessReturnRequestCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $id = new ReturnRequestId($command->returnRequestId);
            $returnRequest = $this->returns->lockForUpdate($id);
            $before = ['resolution_state' => $returnRequest->resolutionState()];

            $returnRequest->assess();
            $this->returns->save($returnRequest);

            $this->audit->record(
                module: 'Warranty',
                eventType: 'ReturnUnderAssessment',
                actorStaffId: new StaffId($command->assessedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'return_request',
                subjectId: $id->value,
                beforeState: $before,
                afterState: ['resolution_state' => $returnRequest->resolutionState()],
            );
        });
    }
}
