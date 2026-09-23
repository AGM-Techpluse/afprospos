<?php

declare(strict_types=1);

namespace Domain\Warranty\Application\Handlers;

use App\Support\Transactions\Atomic;
use Carbon\CarbonImmutable;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Sales\Application\Commands\ApplyDiscountCommand;
use Domain\Sales\Application\Contracts\CheckoutDiscountService;
use Domain\Sales\Application\Contracts\SaleLookup;
use Domain\Shared\Domain\ValueObjects\StaffId;
use Domain\Warranty\Application\Commands\ProcessReturnExchangeCommand;
use Domain\Warranty\Domain\Events\ReturnRequestResolved;
use Domain\Warranty\Domain\Exceptions\ExchangeCreditCouldNotBeApplied;
use Domain\Warranty\Domain\Repositories\ReturnRequestRepository;
use Domain\Warranty\Domain\ValueObjects\ReturnRequestId;
use Illuminate\Support\Facades\Event;

/**
 * WAR-BR-13 ("Sale Callback"): the "Sale Exchange" path — mirrors
 * ProcessReturnRefundHandler's separation-of-duties shape, but instead of
 * moving money back through the original payment method, applies the
 * sale's full paid value as a `store_credit` adjustment on a new
 * checkout via Sales' published CheckoutDiscountService (the same seam
 * Warranty's trade-in credit already uses), so the customer never pays
 * twice for value they've already handed over.
 */
final class ProcessReturnExchangeHandler
{
    public function __construct(
        private readonly ReturnRequestRepository $returns,
        private readonly SaleLookup $saleLookup,
        private readonly CheckoutDiscountService $checkoutDiscounts,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(ProcessReturnExchangeCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $id = new ReturnRequestId($command->returnRequestId);
            $returnRequest = $this->returns->lockForUpdate($id);
            $before = ['resolution_state' => $returnRequest->resolutionState()];

            $sale = $this->saleLookup->find($returnRequest->saleId());

            if ($sale === null) {
                throw ExchangeCreditCouldNotBeApplied::saleNotFound($returnRequest->saleId());
            }

            $applied = $this->checkoutDiscounts->apply(new ApplyDiscountCommand(
                checkoutId: $command->newCheckoutId,
                type: 'store_credit',
                sourceId: $id->value,
                amountMinor: $sale['total_minor'],
            ));

            if (! $applied) {
                throw ExchangeCreditCouldNotBeApplied::checkoutNotOpen($command->newCheckoutId);
            }

            $returnRequest->resolveAsExchange($command->newCheckoutId);
            $this->returns->save($returnRequest);

            $this->audit->record(
                module: 'Warranty',
                eventType: 'ReturnResolvedAsExchange',
                actorStaffId: new StaffId($command->processedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'return_request',
                subjectId: $id->value,
                beforeState: $before,
                afterState: ['resolution_state' => $returnRequest->resolutionState(), 'resolution_type' => 'exchange', 'exchange_checkout_id' => $command->newCheckoutId],
            );

            Event::dispatch(new ReturnRequestResolved($id->value, $returnRequest->customerId(), CarbonImmutable::now()));
        });
    }
}
