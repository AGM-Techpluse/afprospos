<?php

declare(strict_types=1);

namespace Domain\Finance\Application\Handlers;

use App\Support\Transactions\Atomic;
use DateTimeImmutable;
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
                name: $command->name,
                shopId: $command->shopId,
                department: $command->department,
                category: $command->category,
                amountMinor: $command->amountMinor,
                expenseDate: new DateTimeImmutable($command->expenseDate),
                paymentMethod: $command->paymentMethod,
                proofPath: $command->proofPath,
                description: $command->description,
                fundType: $command->fundType,
                reimbursedToStaffId: $command->reimbursedToStaffId,
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
                    'name' => $command->name,
                    'shop_id' => $command->shopId,
                    'department' => $command->department,
                    'category' => $command->category,
                    'amount_minor' => $command->amountMinor,
                    'fund_type' => $command->fundType,
                    'reimbursed_to_staff_id' => $command->reimbursedToStaffId,
                ],
            );

            return $expenseId;
        });
    }
}
