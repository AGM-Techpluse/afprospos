<?php

declare(strict_types=1);

namespace Domain\Commission\Infrastructure\Persistence\Repositories;

use Domain\Commission\Domain\Entities\CommissionLedgerEntry;
use Domain\Commission\Domain\Repositories\CommissionLedgerEntryRepository;
use Domain\Commission\Domain\ValueObjects\CommissionLedgerEntryId;
use Domain\Commission\Infrastructure\Persistence\Eloquent\CommissionLedgerEntryRecord;

final class EloquentCommissionLedgerEntryRepository implements CommissionLedgerEntryRepository
{
    public function append(CommissionLedgerEntry $entry): CommissionLedgerEntryId
    {
        $record = CommissionLedgerEntryRecord::query()->create([
            'staff_id' => $entry->staffId()->value,
            'source_type' => $entry->sourceType(),
            'source_id' => $entry->sourceId(),
            'commissionable_amount_minor' => $entry->commissionableAmount()->minor,
            'rate_snapshot_percent' => $entry->rateSnapshotPercent(),
            'role_snapshot' => $entry->roleSnapshot(),
            'entry_type' => $entry->entryType(),
            'reversal_of_entry_id' => $entry->reversalOfEntryId(),
            'status' => $entry->status(),
            'shop_id' => $entry->shopId()->value,
        ]);

        return new CommissionLedgerEntryId($record->id);
    }
}
