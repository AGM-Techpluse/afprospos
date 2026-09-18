<?php

declare(strict_types=1);

namespace Domain\Warranty\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Sales\Application\Commands\ApplyDiscountCommand;
use Domain\Sales\Application\Contracts\CheckoutDiscountService;
use Domain\Shared\Domain\ValueObjects\StaffId;
use Domain\Warranty\Application\Commands\ApproveTradeInCommand;
use Domain\Warranty\Domain\Repositories\TradeInAssessmentRepository;
use Domain\Warranty\Domain\ValueObjects\TradeInAssessmentId;

/**
 * TRADE-BR-02/05: approves the assessed value (enforcing the
 * separate-approver-from-assessor check inside the entity), then — if
 * a checkout was linked at submission time and is still open — applies
 * the credit in the same step. If the checkout is missing or no longer
 * open, the assessment simply stays `approved`; `ApplyTradeInCreditHandler`
 * is the manual fallback for that case.
 */
final class ApproveTradeInHandler
{
    public function __construct(
        private readonly TradeInAssessmentRepository $tradeIns,
        private readonly CheckoutDiscountService $checkoutDiscounts,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(ApproveTradeInCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $id = new TradeInAssessmentId($command->tradeInAssessmentId);
            $tradeIn = $this->tradeIns->lockForUpdate($id);
            $before = ['resolution_state' => $tradeIn->resolutionState()];

            $tradeIn->approve($command->approvedByStaffId);

            $applied = false;

            if ($tradeIn->relatedCheckoutId() !== null) {
                $applied = $this->checkoutDiscounts->apply(new ApplyDiscountCommand(
                    checkoutId: $tradeIn->relatedCheckoutId(),
                    type: 'trade_in_credit',
                    sourceId: $id->value,
                    amountMinor: $tradeIn->assessedValueMinor(),
                ));

                // If the checkout closed since the trade-in was linked, this returns false and the assessment stays `approved`; staff applies the credit manually to a different checkout.
                if ($applied) {
                    $tradeIn->apply($tradeIn->relatedCheckoutId());
                }
            }

            $this->tradeIns->save($tradeIn);

            $this->audit->record(
                module: 'Warranty',
                eventType: 'TradeInApproved',
                actorStaffId: new StaffId($command->approvedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'trade_in_assessment',
                subjectId: $id->value,
                beforeState: $before,
                afterState: ['resolution_state' => $tradeIn->resolutionState(), 'credit_applied' => $applied],
            );
        });
    }
}
