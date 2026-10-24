<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Repositories;

use Domain\Repair\Domain\Entities\RepairSupplementalQuote;
use Domain\Repair\Domain\ValueObjects\RepairJobId;
use Domain\Repair\Domain\ValueObjects\RepairSupplementalQuoteId;

interface RepairSupplementalQuoteRepository
{
    public function get(RepairSupplementalQuoteId $id): RepairSupplementalQuote;

    public function lockForUpdate(RepairSupplementalQuoteId $id): RepairSupplementalQuote;

    public function save(RepairSupplementalQuote $quote): RepairSupplementalQuoteId;

    public function findPendingForJob(RepairJobId $repairJobId): ?RepairSupplementalQuote;

    /** @return int sum of amount_minor for every `approved` row against this job */
    public function sumApprovedForJob(RepairJobId $repairJobId): int;
}
