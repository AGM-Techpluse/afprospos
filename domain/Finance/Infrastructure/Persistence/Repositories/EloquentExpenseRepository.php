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
            'shop_id' => $expense->shopId(),
            'category' => $expense->category(),
            'amount_minor' => $expense->amountMinor(),
            'allocation' => $expense->allocation(),
            'note' => $expense->note(),
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
            $record->shop_id,
            $record->category,
            $record->amount_minor,
            $record->allocation,
            $record->note,
            $record->recorded_by_staff_id,
        );
    }
}
