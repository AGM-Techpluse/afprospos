<?php

declare(strict_types=1);

namespace Domain\Warranty\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Shared\Domain\ValueObjects\StaffId;
use Domain\Warranty\Application\Commands\RejectTradeInCommand;
use Domain\Warranty\Domain\Repositories\TradeInAssessmentRepository;
use Domain\Warranty\Domain\ValueObjects\TradeInAssessmentId;

final class RejectTradeInHandler
{
    public function __construct(
        private readonly TradeInAssessmentRepository $tradeIns,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(RejectTradeInCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $id = new TradeInAssessmentId($command->tradeInAssessmentId);
            $tradeIn = $this->tradeIns->lockForUpdate($id);
            $before = ['resolution_state' => $tradeIn->resolutionState()];

            $tradeIn->reject($command->rejectedByStaffId);
            $this->tradeIns->save($tradeIn);

            $this->audit->record(
                module: 'Warranty',
                eventType: 'TradeInRejected',
                actorStaffId: new StaffId($command->rejectedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'trade_in_assessment',
                subjectId: $id->value,
                beforeState: $before,
                afterState: ['resolution_state' => $tradeIn->resolutionState()],
            );
        });
    }
}
