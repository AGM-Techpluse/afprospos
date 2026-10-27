<?php

declare(strict_types=1);

namespace Domain\Finance\Application\Handlers;

use App\Support\Transactions\Atomic;
use DateTimeImmutable;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Finance\Application\Commands\UpdateExpenseCommand;
use Domain\Finance\Domain\Repositories\ExpenseRepository;
use Domain\Finance\Domain\ValueObjects\ExpenseId;
use Domain\Shared\Domain\ValueObjects\StaffId;
use Illuminate\Support\Facades\Storage;

final class UpdateExpenseHandler
{
    public function __construct(
        private readonly ExpenseRepository $expenses,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(UpdateExpenseCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $expenseId = new ExpenseId($command->expenseId);
            $expense = $this->expenses->get($expenseId);
            $before = ['name' => $expense->name(), 'category' => $expense->category(), 'amount_minor' => $expense->amountMinor()];

            $expense->update(
                name: $command->name,
                department: $command->department,
                category: $command->category,
                amountMinor: $command->amountMinor,
                expenseDate: new DateTimeImmutable($command->expenseDate),
                paymentMethod: $command->paymentMethod,
                description: $command->description,
                fundType: $command->fundType,
                reimbursedToStaffId: $command->reimbursedToStaffId,
            );

            if ($command->newProofPath !== null) {
                $previousProofPath = $expense->proofPath();
                $expense->replaceProof($command->newProofPath);

                if ($previousProofPath !== null) {
                    Storage::disk('local')->delete($previousProofPath);
                }
            }

            $this->expenses->save($expense);

            $this->audit->record(
                module: 'Finance',
                eventType: 'ExpenseUpdated',
                actorStaffId: new StaffId($command->updatedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'expense',
                subjectId: $expenseId->value,
                beforeState: $before,
                afterState: ['name' => $command->name, 'category' => $command->category, 'amount_minor' => $command->amountMinor],
            );
        });
    }
}
