<?php

declare(strict_types=1);

namespace Domain\Finance\Infrastructure\Persistence\Repositories;

use Domain\Finance\Domain\Entities\Expense;
use Domain\Finance\Domain\Repositories\ExpenseRepository;
use Domain\Finance\Domain\ValueObjects\ExpenseId;
use Domain\Finance\Infrastructure\Persistence\Eloquent\ExpenseRecord;

final class EloquentExpenseRepository implements ExpenseRepository
{
    public function get(ExpenseId $id): Expense
    {
        return $this->toDomain(ExpenseRecord::query()->findOrFail($id->value));
    }

    public function save(Expense $expense): ExpenseId
    {
        $attributes = [
            'name' => $expense->name(),
            'shop_id' => $expense->shopId(),
            'department' => $expense->department(),
            'category' => $expense->category(),
            'amount_minor' => $expense->amountMinor(),
            'expense_date' => $expense->expenseDate()->format('Y-m-d'),
            'payment_method' => $expense->paymentMethod(),
            'proof_path' => $expense->proofPath(),
            'description' => $expense->description(),
            'fund_type' => $expense->fundType(),
            'reimbursed_to_staff_id' => $expense->reimbursedToStaffId(),
            'recorded_by_staff_id' => $expense->recordedByStaffId(),
        ];

        if ($expense->id() === null) {
            $record = ExpenseRecord::query()->create($attributes);
        } else {
            $record = ExpenseRecord::query()->findOrFail($expense->id()->value);
            $record->update($attributes);
        }

        return new ExpenseId($record->id);
    }

    private function toDomain(ExpenseRecord $record): Expense
    {
        return Expense::reconstitute(
            new ExpenseId($record->id),
            $record->name,
            $record->shop_id,
            $record->department,
            $record->category,
            $record->amount_minor,
            $record->expense_date->toDateTimeImmutable(),
            $record->payment_method,
            $record->proof_path,
            $record->description,
            $record->fund_type,
            $record->reimbursed_to_staff_id,
            $record->recorded_by_staff_id,
        );
    }
}
