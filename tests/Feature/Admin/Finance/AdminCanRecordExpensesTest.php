<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Finance;

use Database\Seeders\RolePermissionSeeder;
use Domain\Finance\Infrastructure\Persistence\Eloquent\ExpenseRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminCanRecordExpensesTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'October rent',
            'shop_id' => null,
            'department' => 'finance',
            'category' => 'rent',
            'amount_minor' => 50000_00,
            'expense_date' => now()->toDateString(),
            'payment_method' => 'bank_transfer',
            'description' => 'Main office rent',
            'fund_type' => 'disbursement',
            'reimbursed_to_staff_id' => null,
        ], $overrides);
    }

    public function test_an_accountant_can_record_a_business_wide_disbursement(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $accountant = StaffRecord::factory()->create();
        $accountant->assignRole('Accountant');

        $response = $this->actingAs($accountant, 'staff')->post('/admin/finance/expenses', $this->validPayload());

        $response->assertRedirect('/admin/finance/expenses');
        $this->assertDatabaseHas('expenses', [
            'name' => 'October rent',
            'shop_id' => null,
            'department' => 'finance',
            'category' => 'rent',
            'amount_minor' => 50000_00,
            'payment_method' => 'bank_transfer',
            'fund_type' => 'disbursement',
            'reimbursed_to_staff_id' => null,
            'recorded_by_staff_id' => $accountant->id,
        ]);
    }

    public function test_a_reimbursement_requires_naming_the_staff_member_being_paid_back(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $technician = StaffRecord::factory()->create();

        $response = $this->actingAs($owner, 'staff')->post('/admin/finance/expenses', $this->validPayload([
            'fund_type' => 'reimbursement',
            'reimbursed_to_staff_id' => $technician->id,
            'category' => 'supplies',
        ]));

        $response->assertRedirect();
        $this->assertDatabaseHas('expenses', ['fund_type' => 'reimbursement', 'reimbursed_to_staff_id' => $technician->id]);
    }

    public function test_reimbursement_without_a_named_staff_member_is_rejected(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        $response = $this->actingAs($owner, 'staff')
            ->from('/admin/finance/expenses/create')
            ->post('/admin/finance/expenses', $this->validPayload(['fund_type' => 'reimbursement', 'reimbursed_to_staff_id' => null]));

        $response->assertSessionHasErrors('reimbursed_to_staff_id');
        $this->assertDatabaseMissing('expenses', ['fund_type' => 'reimbursement']);
    }

    public function test_an_expense_can_be_scoped_to_a_shop_with_an_uploaded_proof(): void
    {
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('local');

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $shop = ShopRecord::factory()->create();

        $this->actingAs($owner, 'staff')->post('/admin/finance/expenses', $this->validPayload([
            'shop_id' => $shop->id,
            'category' => 'utilities',
            'proof' => UploadedFile::fake()->create('receipt.pdf', 200, 'application/pdf'),
        ]))->assertRedirect();

        $expense = ExpenseRecord::query()->first();
        $this->assertSame($shop->id, $expense->shop_id);
        $this->assertNotNull($expense->proof_path);
        Storage::disk('local')->assertExists($expense->proof_path);
    }

    public function test_staff_can_download_the_proof_document(): void
    {
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('local');

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        $this->actingAs($owner, 'staff')->post('/admin/finance/expenses', $this->validPayload([
            'proof' => UploadedFile::fake()->create('receipt.pdf', 200, 'application/pdf'),
        ]));
        $expense = ExpenseRecord::query()->first();

        $this->actingAs($owner, 'staff')->get("/admin/finance/expenses/{$expense->id}/proof")->assertOk();
    }

    public function test_a_guest_cannot_download_the_proof_document(): void
    {
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('local');

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        // Created directly (not via an authenticated HTTP call) — actingAs() isn't
        // request-scoped, so any call made in a test that has used it is never
        // truly a guest request afterward.
        $expense = ExpenseRecord::query()->create([
            'name' => 'October rent',
            'department' => 'finance',
            'category' => 'rent',
            'amount_minor' => 50000_00,
            'expense_date' => now()->toDateString(),
            'payment_method' => 'bank_transfer',
            'proof_path' => 'expenses/fake-proof.pdf',
            'fund_type' => 'disbursement',
            'recorded_by_staff_id' => $owner->id,
        ]);
        Storage::disk('local')->put('expenses/fake-proof.pdf', 'fake contents');

        $this->get("/admin/finance/expenses/{$expense->id}/proof")->assertRedirect('/staff/login');
    }

    public function test_rejects_an_unknown_department(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        $response = $this->actingAs($owner, 'staff')
            ->from('/admin/finance/expenses/create')
            ->post('/admin/finance/expenses', $this->validPayload(['department' => 'made_up']));

        $response->assertSessionHasErrors('department');
        $this->assertDatabaseMissing('expenses', ['department' => 'made_up']);
    }

    public function test_an_expense_can_be_corrected(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        $this->actingAs($owner, 'staff')->post('/admin/finance/expenses', $this->validPayload());
        $expense = ExpenseRecord::query()->first();

        $response = $this->actingAs($owner, 'staff')->put("/admin/finance/expenses/{$expense->id}", [
            'name' => 'Corrected name',
            'department' => $expense->department,
            'category' => 'utilities',
            'amount_minor' => 1200_00,
            'expense_date' => $expense->expense_date->toDateString(),
            'payment_method' => 'cash',
            'description' => 'Corrected amount',
            'fund_type' => 'disbursement',
            'reimbursed_to_staff_id' => null,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'name' => 'Corrected name',
            'category' => 'utilities',
            'amount_minor' => 1200_00,
            'payment_method' => 'cash',
        ]);
    }

    public function test_blocks_a_staff_member_without_expenses_create(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier'); // no expenses.* permissions per config/afprospos.php

        $response = $this->actingAs($cashier, 'staff')->post('/admin/finance/expenses', $this->validPayload());

        $response->assertForbidden();
        $this->assertDatabaseMissing('expenses', ['name' => 'October rent']);
    }

    public function test_blocks_a_staff_member_without_expenses_view_from_the_list(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');

        $this->actingAs($cashier, 'staff')->get('/admin/finance/expenses')->assertForbidden();
    }
}
