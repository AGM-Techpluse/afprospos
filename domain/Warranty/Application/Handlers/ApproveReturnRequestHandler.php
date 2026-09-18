<?php

declare(strict_types=1);

namespace Domain\Warranty\Application\Handlers;

use App\Support\Transactions\Atomic;
use Carbon\CarbonImmutable;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Shared\Domain\ValueObjects\StaffId;
use Domain\Warranty\Application\Commands\ApproveReturnRequestCommand;
use Domain\Warranty\Domain\Repositories\ReturnRequestRepository;
use Domain\Warranty\Domain\ValueObjects\ReturnRequestId;

final class ApproveReturnRequestHandler
{
    public function __construct(
        private readonly ReturnRequestRepository $returns,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(ApproveReturnRequestCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $id = new ReturnRequestId($command->returnRequestId);
            $returnRequest = $this->returns->lockForUpdate($id);
            $before = ['resolution_state' => $returnRequest->resolutionState()];

            $returnRequest->approve(CarbonImmutable::now());
            $this->returns->save($returnRequest);

            $this->audit->record(
                module: 'Warranty',
                eventType: 'ReturnApproved',
                actorStaffId: new StaffId($command->approvedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'return_request',
                subjectId: $id->value,
                beforeState: $before,
                afterState: ['resolution_state' => $returnRequest->resolutionState()],
            );
        });
    }
}
