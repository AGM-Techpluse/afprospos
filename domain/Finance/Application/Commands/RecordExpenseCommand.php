<?php

declare(strict_types=1);

namespace Domain\Finance\Application\Commands;

final readonly class RecordExpenseCommand
{
    /** @param  array<string, int>|null  $allocation */
    public function __construct(
        public ?int $shopId,
        public string $category,
        public int $amountMinor,
        public ?array $allocation,
        public ?string $note,
        public int $recordedByStaffId,
    ) {}
}
