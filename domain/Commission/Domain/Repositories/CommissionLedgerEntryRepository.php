<?php

declare(strict_types=1);

namespace Domain\Commission\Domain\Repositories;

use Domain\Commission\Domain\Entities\CommissionLedgerEntry;
use Domain\Commission\Domain\ValueObjects\CommissionLedgerEntryId;

/** The ledger is insert-only (DBDD §17.1) — there is no update/delete path, a correction is always a new entry. */
interface CommissionLedgerEntryRepository
{
    public function append(CommissionLedgerEntry $entry): CommissionLedgerEntryId;
}
