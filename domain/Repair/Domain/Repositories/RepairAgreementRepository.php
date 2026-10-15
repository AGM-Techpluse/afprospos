<?php

declare(strict_types=1);

namespace Domain\Repair\Domain\Repositories;

/** Special agreements (e.g. a promo, a policy exception, a warranty arrangement) agreed with the customer when a quote is authorized. */
interface RepairAgreementRepository
{
    public function record(int $repairJobId, string $note, int $recordedByStaffId): void;

    public function latestNote(int $repairJobId): ?string;
}
