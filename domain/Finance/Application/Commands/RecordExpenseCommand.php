<?php

declare(strict_types=1);

namespace Domain\Finance\Application\Commands;

final readonly class RecordExpenseCommand
{
    public function __construct(
        public string $name,
        public ?int $shopId,
        public string $department,
        public string $category,
        public int $amountMinor,
        public string $expenseDate,
        public string $paymentMethod,
        public ?string $proofPath,
        public ?string $description,
        public string $fundType,
        public ?int $reimbursedToStaffId,
        public int $recordedByStaffId,
    ) {}
}
