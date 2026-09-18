<?php

declare(strict_types=1);

namespace Domain\Warranty\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Shared\Domain\ValueObjects\StaffId;
use Domain\Warranty\Application\Commands\SubmitTradeInAssessmentCommand;
use Domain\Warranty\Domain\Entities\TradeInAssessment;
use Domain\Warranty\Domain\Repositories\TradeInAssessmentRepository;

final class SubmitTradeInAssessmentHandler
{
    public function __construct(
        private readonly TradeInAssessmentRepository $tradeIns,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(SubmitTradeInAssessmentCommand $command): int
    {
        return $this->atomic->run(function () use ($command): int {
            $tradeIn = TradeInAssessment::submit($command->customerId, $command->relatedCheckoutId, $command->deviceDescription);
            $id = $this->tradeIns->save($tradeIn);

            $this->audit->record(
                module: 'Warranty',
                eventType: 'TradeInSubmitted',
                actorStaffId: new StaffId($command->submittedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'trade_in_assessment',
                subjectId: $id->value,
                beforeState: null,
                afterState: [
                    'customer_id' => $command->customerId,
                    'related_checkout_id' => $command->relatedCheckoutId,
                    'device_description' => $command->deviceDescription,
                ],
            );

            return $id->value;
        });
    }
}
