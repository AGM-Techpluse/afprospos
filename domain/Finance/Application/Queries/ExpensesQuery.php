<?php

declare(strict_types=1);

namespace Domain\Finance\Application\Queries;

use Domain\Finance\Infrastructure\Persistence\Eloquent\ExpenseRecord;
use Domain\Identity\Application\Queries\StaffDirectoryQuery;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;

/** Read-side query backing Admin Finance/Expenses/Index. */
final class ExpensesQuery
{
    public function __construct(
        private readonly StaffDirectoryQuery $staff,
    ) {}

    /** @return array{data: array<int, array<string, mixed>>, current_page:int, last_page:int, per_page:int, total:int} */
    public function paginate(?int $shopId, ?string $category, ?string $department, int $page, int $perPage = 20): array
    {
        $query = ExpenseRecord::query();

        if ($shopId !== null) {
            $query->where('shop_id', $shopId);
        }

        if ($category !== null && $category !== '') {
            $query->where('category', $category);
        }

        if ($department !== null && $department !== '') {
            $query->where('department', $department);
        }

        $paginator = $query->orderByDesc('expense_date')->orderByDesc('id')->paginate($perPage, ['*'], 'page', $page);

        $shopNames = ShopRecord::query()->pluck('name', 'id');

        return [
            'data' => $paginator->getCollection()->map(function (ExpenseRecord $expense) use ($shopNames): array {
                return [
                    'id' => $expense->id,
                    'name' => $expense->name,
                    'shop_id' => $expense->shop_id,
                    'shop_name' => $expense->shop_id !== null ? ($shopNames[$expense->shop_id] ?? 'Unknown shop') : null,
                    'department' => $expense->department,
                    'category' => $expense->category,
                    'amount_minor' => $expense->amount_minor,
                    'expense_date' => $expense->expense_date->toDateString(),
                    'payment_method' => $expense->payment_method,
                    'has_proof' => $expense->proof_path !== null,
                    'description' => $expense->description,
                    'fund_type' => $expense->fund_type,
                    'reimbursed_to_staff_id' => $expense->reimbursed_to_staff_id,
                    'reimbursed_to_staff_name' => $expense->reimbursed_to_staff_id !== null
                        ? ($this->staff->find($expense->reimbursed_to_staff_id)['name'] ?? null)
                        : null,
                    'recorded_by_staff_id' => $expense->recorded_by_staff_id,
                    'recorded_by_staff_name' => $this->staff->find($expense->recorded_by_staff_id)['name'] ?? null,
                    'created_at' => $expense->created_at->toIso8601String(),
                ];
            })->all(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }

    /** Sum of expenses matching the filters — backs a simple total on the Index page. */
    public function totalMinor(?int $shopId, ?string $category, ?string $department): int
    {
        $query = ExpenseRecord::query();

        if ($shopId !== null) {
            $query->where('shop_id', $shopId);
        }

        if ($category !== null && $category !== '') {
            $query->where('category', $category);
        }

        if ($department !== null && $department !== '') {
            $query->where('department', $department);
        }

        return (int) $query->sum('amount_minor');
    }

    /** @return array{proof_path: string}|null */
    public function proofPathFor(int $expenseId): ?array
    {
        $path = ExpenseRecord::query()->whereKey($expenseId)->value('proof_path');

        return $path !== null ? ['proof_path' => $path] : null;
    }
}
