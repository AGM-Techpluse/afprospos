<?php

declare(strict_types=1);

namespace Domain\Finance\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Finance\Application\Commands\RecordExpenseCommand;
use Domain\Finance\Domain\Entities\Expense;
use Domain\Finance\Domain\Repositories\ExpenseRepository;
use Domain\Finance\Domain\ValueObjects\ExpenseId;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class RecordExpenseHandler
{
    public function __construct(
        private readonly ExpenseRepository $expenses,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(RecordExpenseCommand $command): ExpenseId
    {
        return $this->atomic->run(function () use ($command): ExpenseId {
            $expense = Expense::record(
                shopId: $command->shopId,
                category: $command->category,
                amountMinor: $command->amountMinor,
                allocation: $command->allocation,
                note: $command->note,
                recordedByStaffId: $command->recordedByStaffId,
            );

            $expenseId = $this->expenses->save($expense);

            $this->audit->record(
                module: 'Finance',
                eventType: 'ExpenseRecorded',
                actorStaffId: new StaffId($command->recordedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'expense',
                subjectId: $expenseId->value,
                beforeState: null,
                afterState: [
                    'shop_id' => $command->shopId,
                    'category' => $command->category,
                    'amount_minor' => $command->amountMinor,
                ],
            );

            return $expenseId;
        });
    }
}
