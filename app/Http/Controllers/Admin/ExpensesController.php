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
use Domain\Identity\Application\Queries\StaffDirectoryQuery;
use Domain\Shared\Application\DTOs\ActorContext;
use Domain\Shop\Application\Queries\AccessibleShopsQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExpensesController
{
    use FlashesToast;

    public function __construct(
        private readonly ExpensesQuery $expenses,
        private readonly AccessibleShopsQuery $accessibleShops,
        private readonly StaffDirectoryQuery $staff,
        private readonly RecordExpenseHandler $recordExpense,
        private readonly UpdateExpenseHandler $updateExpense,
    ) {}

    public function index(Request $request): Response
    {
        $actor = app(ActorContext::class);
        $shopId = $request->has('shop_id') ? ($request->integer('shop_id') ?: null) : null;
        $category = $request->string('category')->toString() ?: null;
        $department = $request->string('department')->toString() ?: null;
        abort_if($shopId !== null && ! $actor->canAccessShop($shopId), 403);

        return Inertia::render('Admin/Finance/Expenses/Index', [
            'expenses' => $this->expenses->paginate(shopId: $shopId, category: $category, department: $department, page: $request->integer('page', 1)),
            'totalMinor' => $this->expenses->totalMinor($shopId, $category, $department),
            'filters' => ['shop_id' => $shopId, 'category' => $category ?? '', 'department' => $department ?? ''],
            'shops' => $this->accessibleShops->forStaff($actor->staffId->value, $actor->isOwner),
            'categories' => config('afprospos.expense_categories'),
            'departments' => config('afprospos.expense_departments'),
            'paymentMethods' => config('afprospos.expense_payment_methods'),
            'staffOptions' => $this->staffOptions(),
        ]);
    }

    public function create(): Response
    {
        $actor = app(ActorContext::class);

        return Inertia::render('Admin/Finance/Expenses/Create', [
            'shops' => $this->accessibleShops->forStaff($actor->staffId->value, $actor->isOwner),
            'categories' => config('afprospos.expense_categories'),
            'departments' => config('afprospos.expense_departments'),
            'paymentMethods' => config('afprospos.expense_payment_methods'),
            'staffOptions' => $this->staffOptions(),
        ]);
    }

    public function store(RecordExpenseRequest $request): RedirectResponse
    {
        $actor = app(ActorContext::class);
        $shopId = $request->integer('shop_id') ?: null;
        abort_if($shopId !== null && ! $actor->canAccessShop($shopId), 403);

        $this->recordExpense->handle(new RecordExpenseCommand(
            name: $request->string('name')->toString(),
            shopId: $shopId,
            department: $request->string('department')->toString(),
            category: $request->string('category')->toString(),
            amountMinor: $request->integer('amount_minor'),
            expenseDate: $request->string('expense_date')->toString(),
            paymentMethod: $request->string('payment_method')->toString(),
            proofPath: $this->storeProof($request),
            description: $request->string('description')->toString() ?: null,
            fundType: $request->string('fund_type')->toString(),
            reimbursedToStaffId: $request->integer('reimbursed_to_staff_id') ?: null,
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
            name: $request->string('name')->toString(),
            department: $request->string('department')->toString(),
            category: $request->string('category')->toString(),
            amountMinor: $request->integer('amount_minor'),
            expenseDate: $request->string('expense_date')->toString(),
            paymentMethod: $request->string('payment_method')->toString(),
            description: $request->string('description')->toString() ?: null,
            fundType: $request->string('fund_type')->toString(),
            reimbursedToStaffId: $request->integer('reimbursed_to_staff_id') ?: null,
            updatedByStaffId: $actor->staffId->value,
            newProofPath: $this->storeProof($request),
        ));

        $this->flashSuccess('Expense updated');

        return redirect()->back();
    }

    /**
     * Streams the receipt/invoice directly — stored on the private `local`
     * disk (storage/app/private), never a public URL, since a financial
     * document shouldn't be reachable by anyone who merely guesses or
     * stumbles on the link.
     */
    public function downloadProof(int $expense): StreamedResponse
    {
        $record = $this->expenses->proofPathFor($expense);
        abort_if($record === null, 404);

        abort_unless(Storage::disk('local')->exists($record['proof_path']), 404);

        return Storage::disk('local')->download($record['proof_path']);
    }

    private function storeProof(Request $request): ?string
    {
        if (! $request->hasFile('proof')) {
            return null;
        }

        $file = $request->file('proof');

        return $file->storeAs('expenses', Str::uuid()->toString().'.'.$file->getClientOriginalExtension(), 'local');
    }

    /** @return array<int, array{id: int, name: string}> */
    private function staffOptions(): array
    {
        return array_map(
            static fn (array $staff): array => ['id' => $staff['id'], 'name' => $staff['name']],
            $this->staff->paginate(search: null, status: 'active', role: null, page: 1, perPage: 200)['data'],
        );
    }
}
