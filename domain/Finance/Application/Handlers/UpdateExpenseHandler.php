<?php

declare(strict_types=1);

namespace Domain\Finance\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Finance\Application\Commands\UpdateExpenseCommand;
use Domain\Finance\Domain\Repositories\ExpenseRepository;
use Domain\Finance\Domain\ValueObjects\ExpenseId;
use Domain\Shared\Domain\ValueObjects\StaffId;

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
            $before = ['category' => $expense->category(), 'amount_minor' => $expense->amountMinor()];

            $expense->update($command->category, $command->amountMinor, $command->allocation, $command->note);
            $this->expenses->save($expense);

            $this->audit->record(
                module: 'Finance',
                eventType: 'ExpenseUpdated',
                actorStaffId: new StaffId($command->updatedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'expense',
                subjectId: $expenseId->value,
                beforeState: $before,
                afterState: ['category' => $command->category, 'amount_minor' => $command->amountMinor],
            );
        });
    }
}
