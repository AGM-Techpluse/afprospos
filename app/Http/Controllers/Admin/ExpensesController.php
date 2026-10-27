<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\FlashesToast;
use App\Http\Requests\Admin\RecordExpenseRequest;
use App\Http\Requests\Admin\UpdateExpenseRequest;
use Domain\Finance\Application\Commands\RecordExpenseCommand;
use Domain\Finance\Application\Commands\UpdateExpenseCommand;
use Domain\Finance\Application\Handlers\RecordExpenseHandler;
use Domain\Finance\Application\Handlers\UpdateExpenseHandler;
use Domain\Finance\Application\Queries\ExpensesQuery;
use Domain\Shared\Application\DTOs\ActorContext;
use Domain\Shop\Application\Queries\AccessibleShopsQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ExpensesController
{
    use FlashesToast;

    public function __construct(
        private readonly ExpensesQuery $expenses,
        private readonly AccessibleShopsQuery $accessibleShops,
        private readonly RecordExpenseHandler $recordExpense,
        private readonly UpdateExpenseHandler $updateExpense,
    ) {}

    public function index(Request $request): Response
    {
        $actor = app(ActorContext::class);
        $shopId = $request->has('shop_id') ? ($request->integer('shop_id') ?: null) : null;
        $category = $request->string('category')->toString() ?: null;
        abort_if($shopId !== null && ! $actor->canAccessShop($shopId), 403);

        return Inertia::render('Admin/Finance/Expenses/Index', [
            'expenses' => $this->expenses->paginate(shopId: $shopId, category: $category, page: $request->integer('page', 1)),
            'totalMinor' => $this->expenses->totalMinor($shopId, $category),
            'filters' => ['shop_id' => $shopId, 'category' => $category ?? ''],
            'shops' => $this->accessibleShops->forStaff($actor->staffId->value, $actor->isOwner),
            'categories' => config('afprospos.expense_categories'),
        ]);
    }

    public function create(): Response
    {
        $actor = app(ActorContext::class);

        return Inertia::render('Admin/Finance/Expenses/Create', [
            'shops' => $this->accessibleShops->forStaff($actor->staffId->value, $actor->isOwner),
            'categories' => config('afprospos.expense_categories'),
        ]);
    }

    public function store(RecordExpenseRequest $request): RedirectResponse
    {
        $actor = app(ActorContext::class);
        $shopId = $request->integer('shop_id') ?: null;
        abort_if($shopId !== null && ! $actor->canAccessShop($shopId), 403);

        $this->recordExpense->handle(new RecordExpenseCommand(
            shopId: $shopId,
            category: $request->string('category')->toString(),
            amountMinor: $request->integer('amount_minor'),
            allocation: $request->has('allocation') ? $request->array('allocation') : null,
            note: $request->string('note')->toString() ?: null,
            recordedByStaffId: $actor->staffId->value,
        ));

        $this->flashSuccess('Expense recorded');

        return redirect()->route('admin.finance.expenses.index');
    }

    public function update(UpdateExpenseRequest $request, int $expense): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->updateExpense->handle(new UpdateExpenseCommand(
            expenseId: $expense,
            category: $request->string('category')->toString(),
            amountMinor: $request->integer('amount_minor'),
            allocation: $request->has('allocation') ? $request->array('allocation') : null,
            note: $request->string('note')->toString() ?: null,
            updatedByStaffId: $actor->staffId->value,
        ));

        $this->flashSuccess('Expense updated');

        return redirect()->back();
    }
}
