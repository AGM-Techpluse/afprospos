<?php

declare(strict_types=1);

namespace Domain\Commission\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Commission\Application\Commands\RecordCommissionEarnedCommand;
use Domain\Commission\Domain\Entities\CommissionLedgerEntry;
use Domain\Commission\Domain\Events\CommissionEarned;
use Domain\Commission\Domain\Repositories\CommissionLedgerEntryRepository;
use Domain\Shared\Domain\ValueObjects\Money;
use Domain\Shared\Domain\ValueObjects\ShopId;
use Domain\Shared\Domain\ValueObjects\StaffId;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Support\Facades\Event;

final class RecordCommissionEarnedHandler
{
    public function __construct(
        private readonly CommissionLedgerEntryRepository $entries,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(RecordCommissionEarnedCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $roleSnapshot = StaffRecord::query()->find($command->staffId)?->getRoleNames()->first() ?? 'Unknown';

            $entry = CommissionLedgerEntry::earn(
                staffId: new StaffId($command->staffId),
                sourceType: $command->sourceType,
                sourceId: $command->sourceId,
                amount: new Money($command->amountMinor),
                roleSnapshot: $roleSnapshot,
                shopId: new ShopId($command->shopId),
            );

            $entryId = $this->entries->append($entry);

            $this->audit->record(
                module: 'Commission',
                eventType: 'CommissionEarned',
                actorStaffId: null,
                actorRoleSnapshot: null,
                subjectType: 'commission_ledger_entry',
                subjectId: $entryId->value,
                beforeState: null,
                afterState: [
                    'staff_id' => $command->staffId,
                    'source_type' => $command->sourceType,
                    'source_id' => $command->sourceId,
                    'amount_minor' => $command->amountMinor,
                ],
            );

            Event::dispatch(new CommissionEarned(
                entryId: $entryId->value,
                staffId: $command->staffId,
                sourceType: $command->sourceType,
                sourceId: $command->sourceId,
                amountMinor: $command->amountMinor,
            ));
        });
    }
}
