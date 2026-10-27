<?php

declare(strict_types=1);

namespace Domain\Finance\Application\Commands;

final readonly class UpdateExpenseCommand
{
    public function __construct(
        public int $expenseId,
        public string $name,
        public string $department,
        public string $category,
        public int $amountMinor,
        public string $expenseDate,
        public string $paymentMethod,
        public ?string $description,
        public string $fundType,
        public ?int $reimbursedToStaffId,
        public int $updatedByStaffId,
        /** Only set when a new proof file was uploaded with this edit — null leaves the existing proof untouched. */
        public ?string $newProofPath = null,
    ) {}
}
