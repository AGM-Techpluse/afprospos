<?php

declare(strict_types=1);

namespace Domain\Warranty\Application\Handlers;

use App\Support\Transactions\Atomic;
use Carbon\CarbonImmutable;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Sales\Application\Contracts\SaleLookup;
use Domain\Shared\Domain\ValueObjects\StaffId;
use Domain\Warranty\Application\Commands\CreateReturnRequestCommand;
use Domain\Warranty\Domain\Entities\ReturnRequest;
use Domain\Warranty\Domain\Exceptions\ReturnDoesNotBelongToCustomer;
use Domain\Warranty\Domain\Repositories\ReturnRequestRepository;

/** BLD §5.2: a Return is distinct from a Warranty Claim — the customer wants to give back a recently purchased product under the return policy, not report a fault. WAR-BR-12: the return window is snapshotted from the sale's date at creation time, never recalculated later from the (possibly since-changed) config value. */
final class CreateReturnRequestHandler
{
    public function __construct(
        private readonly ReturnRequestRepository $returns,
        private readonly SaleLookup $saleLookup,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(CreateReturnRequestCommand $command): int
    {
        return $this->atomic->run(function () use ($command): int {
            $sale = $this->saleLookup->find($command->saleId);

            if ($sale === null || $sale['customer_id'] !== $command->customerId) {
                throw ReturnDoesNotBelongToCustomer::forSale($command->saleId);
            }

            $windowDays = (int) config('afprospos.return_window_days');
            $returnWindowExpiresAt = CarbonImmutable::parse($sale['created_at'])->addDays($windowDays);

            $returnRequest = ReturnRequest::request($command->saleId, $command->customerId, $returnWindowExpiresAt);
            $id = $this->returns->save($returnRequest);

            $this->audit->record(
                module: 'Warranty',
                eventType: 'ReturnRequested',
                actorStaffId: $command->submittedByStaffId !== null ? new StaffId($command->submittedByStaffId) : null,
                actorRoleSnapshot: null,
                subjectType: 'return_request',
                subjectId: $id->value,
                beforeState: null,
                afterState: [
                    'sale_id' => $command->saleId,
                    'customer_id' => $command->customerId,
                    'return_window_expires_at' => $returnWindowExpiresAt->toIso8601String(),
                ],
                context: $command->submittedByStaffId === null ? ['customer_id' => $command->customerId] : null,
            );

            return $id->value;
        });
    }
}
