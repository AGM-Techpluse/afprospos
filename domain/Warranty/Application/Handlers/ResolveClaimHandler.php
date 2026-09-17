<?php

declare(strict_types=1);

namespace Domain\Warranty\Application\Handlers;

use App\Support\Transactions\Atomic;
use Carbon\CarbonImmutable;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Inventory\Application\Contracts\InventoryCatalogQuery;
use Domain\Repair\Application\Contracts\RepairJobLookup;
use Domain\Sales\Application\Contracts\SaleLookup;
use Domain\Shared\Domain\ValueObjects\StaffId;
use Domain\Warranty\Application\Commands\ResolveClaimCommand;
use Domain\Warranty\Domain\Events\WarrantyClaimResolved;
use Domain\Warranty\Domain\Exceptions\InvalidWarrantyClaimSource;
use Domain\Warranty\Domain\Repositories\WarrantyClaimRepository;
use Domain\Warranty\Domain\ValueObjects\WarrantyClaimId;
use Illuminate\Support\Facades\Event;

/**
 * Handles the `repair` and `replace` remedies only — `refund` goes
 * through ApproveRefundHandler (separate permission, WAR-BR-06). Both
 * branches here validate `remedyReferenceId` through the owning
 * module's published Lookup Contract rather than trusting it blind;
 * neither branch reserves/consumes anything in the other module (this
 * slice is reference/association only — see ResolveClaimCommand).
 */
final class ResolveClaimHandler
{
    public function __construct(
        private readonly WarrantyClaimRepository $claims,
        private readonly SaleLookup $saleLookup,
        private readonly RepairJobLookup $repairJobLookup,
        private readonly InventoryCatalogQuery $inventoryCatalog,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(ResolveClaimCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $id = new WarrantyClaimId($command->warrantyClaimId);
            $claim = $this->claims->lockForUpdate($id);
            $before = ['resolution_state' => $claim->resolutionState()];

            $shopId = $this->resolveShopId($claim->originatingSaleId(), $claim->originatingRepairJobId());

            if ($claim->selectedRemedy() === 'repair') {
                $repairJob = $this->repairJobLookup->find($command->remedyReferenceId);

                if ($repairJob === null || $repairJob['customer_id'] !== $claim->customerId()) {
                    throw InvalidWarrantyClaimSource::forRepairJob($command->remedyReferenceId);
                }
            } elseif ($claim->selectedRemedy() === 'replace') {
                $replacement = $this->inventoryCatalog->find($command->remedyReferenceId, $shopId);

                if ($replacement === null) {
                    throw InvalidWarrantyClaimSource::forSale($command->remedyReferenceId);
                }
            }

            $claim->resolve($command->remedyReferenceId);
            $this->claims->save($claim);

            $this->audit->record(
                module: 'Warranty',
                eventType: 'WarrantyClaimResolved',
                actorStaffId: new StaffId($command->resolvedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'warranty_claim',
                subjectId: $id->value,
                beforeState: $before,
                afterState: ['resolution_state' => $claim->resolutionState(), 'remedy_reference_id' => $command->remedyReferenceId],
            );

            Event::dispatch(new WarrantyClaimResolved($id->value, (string) $claim->selectedRemedy(), CarbonImmutable::now()));
        });
    }

    private function resolveShopId(?int $originatingSaleId, ?int $originatingRepairJobId): int
    {
        if ($originatingSaleId !== null) {
            $sale = $this->saleLookup->find($originatingSaleId);

            return $sale['shop_id'];
        }

        $repairJob = $this->repairJobLookup->find($originatingRepairJobId);

        return $repairJob['shop_id'];
    }
}
