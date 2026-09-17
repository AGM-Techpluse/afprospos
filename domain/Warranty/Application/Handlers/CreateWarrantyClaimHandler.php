<?php

declare(strict_types=1);

namespace Domain\Warranty\Application\Handlers;

use App\Support\Transactions\Atomic;
use Carbon\CarbonImmutable;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Repair\Application\Contracts\RepairJobLookup;
use Domain\Sales\Application\Contracts\SaleLookup;
use Domain\Shared\Domain\ValueObjects\StaffId;
use Domain\Warranty\Application\Commands\CreateWarrantyClaimCommand;
use Domain\Warranty\Domain\Entities\WarrantyClaim;
use Domain\Warranty\Domain\Events\WarrantyClaimSubmitted;
use Domain\Warranty\Domain\Exceptions\InvalidWarrantyClaimSource;
use Domain\Warranty\Domain\Repositories\WarrantyClaimRepository;
use Domain\Warranty\Domain\Repositories\WarrantyPolicyRepository;
use Domain\Warranty\Domain\ValueObjects\WarrantyPolicyId;
use Illuminate\Support\Facades\Event;

/** WAR-BR-02/03: a claim must trace back to a real sale or repair, validated through the originating module's published Lookup Contract, never a raw ID trusted from the caller. */
final class CreateWarrantyClaimHandler
{
    public function __construct(
        private readonly WarrantyClaimRepository $claims,
        private readonly WarrantyPolicyRepository $policies,
        private readonly SaleLookup $saleLookup,
        private readonly RepairJobLookup $repairJobLookup,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(CreateWarrantyClaimCommand $command): int
    {
        return $this->atomic->run(function () use ($command): int {
            if ($command->originatingSaleId === null && $command->originatingRepairJobId === null) {
                throw InvalidWarrantyClaimSource::missing();
            }

            // Confirms the policy exists (throws if not) before a claim is ever created against it.
            $this->policies->get(new WarrantyPolicyId($command->warrantyPolicyId));

            $inventoryItemId = null;

            if ($command->originatingSaleId !== null) {
                $sale = $this->saleLookup->find($command->originatingSaleId);

                if ($sale === null || $sale['customer_id'] !== $command->customerId) {
                    throw InvalidWarrantyClaimSource::forSale($command->originatingSaleId);
                }

                if (count($sale['items']) === 1 && $sale['items'][0]['inventory_item_id'] !== null) {
                    $inventoryItemId = $sale['items'][0]['inventory_item_id'];
                }
            }

            if ($command->originatingRepairJobId !== null) {
                $repairJob = $this->repairJobLookup->find($command->originatingRepairJobId);

                if ($repairJob === null || $repairJob['customer_id'] !== $command->customerId) {
                    throw InvalidWarrantyClaimSource::forRepairJob($command->originatingRepairJobId);
                }
            }

            $claim = WarrantyClaim::submit(
                $command->warrantyPolicyId,
                $command->originatingSaleId,
                $command->originatingRepairJobId,
                $command->customerId,
                $inventoryItemId,
            );
            $id = $this->claims->save($claim);

            $this->audit->record(
                module: 'Warranty',
                eventType: 'WarrantyClaimSubmitted',
                actorStaffId: $command->submittedByStaffId !== null ? new StaffId($command->submittedByStaffId) : null,
                actorRoleSnapshot: null,
                subjectType: 'warranty_claim',
                subjectId: $id->value,
                beforeState: null,
                afterState: [
                    'warranty_policy_id' => $command->warrantyPolicyId,
                    'originating_sale_id' => $command->originatingSaleId,
                    'originating_repair_job_id' => $command->originatingRepairJobId,
                    'customer_id' => $command->customerId,
                ],
            );

            Event::dispatch(new WarrantyClaimSubmitted($id->value, $command->warrantyPolicyId, $command->customerId, CarbonImmutable::now()));

            return $id->value;
        });
    }
}
