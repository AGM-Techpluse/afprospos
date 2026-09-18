<?php

declare(strict_types=1);

namespace Domain\Warranty\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Sales\Application\Commands\ApplyDiscountCommand;
use Domain\Sales\Application\Contracts\CheckoutDiscountService;
use Domain\Shared\Domain\ValueObjects\StaffId;
use Domain\Warranty\Application\Commands\ApplyTradeInCreditCommand;
use Domain\Warranty\Domain\Exceptions\TradeInCreditCouldNotBeApplied;
use Domain\Warranty\Domain\Repositories\TradeInAssessmentRepository;
use Domain\Warranty\Domain\ValueObjects\TradeInAssessmentId;

/** The manual fallback for an already-approved trade-in whose credit didn't auto-apply (no checkout linked at approval time, or that checkout has since closed). */
final class ApplyTradeInCreditHandler
{
    public function __construct(
        private readonly TradeInAssessmentRepository $tradeIns,
        private readonly CheckoutDiscountService $checkoutDiscounts,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(ApplyTradeInCreditCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $id = new TradeInAssessmentId($command->tradeInAssessmentId);
            $tradeIn = $this->tradeIns->lockForUpdate($id);
            $before = ['resolution_state' => $tradeIn->resolutionState()];

            $applied = $this->checkoutDiscounts->apply(new ApplyDiscountCommand(
                checkoutId: $command->checkoutId,
                type: 'trade_in_credit',
                sourceId: $id->value,
                amountMinor: $tradeIn->assessedValueMinor(),
            ));

            if (! $applied) {
                throw TradeInCreditCouldNotBeApplied::checkoutNotOpen($command->checkoutId);
            }

            $tradeIn->apply($command->checkoutId);
            $this->tradeIns->save($tradeIn);

            $this->audit->record(
                module: 'Warranty',
                eventType: 'TradeInCreditApplied',
                actorStaffId: new StaffId($command->appliedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'trade_in_assessment',
                subjectId: $id->value,
                beforeState: $before,
                afterState: ['resolution_state' => $tradeIn->resolutionState(), 'checkout_id' => $command->checkoutId],
            );
        });
    }
}
