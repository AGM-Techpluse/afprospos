<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Finance;

use Database\Seeders\RolePermissionSeeder;
use Domain\Finance\Infrastructure\Persistence\Eloquent\ExpenseRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCanRecordExpensesTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_accountant_can_record_a_business_wide_expense(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $accountant = StaffRecord::factory()->create();
        $accountant->assignRole('Accountant');

        $response = $this->actingAs($accountant, 'staff')->post('/admin/finance/expenses', [
            'shop_id' => null,
            'category' => 'rent',
            'amount_minor' => 50000_00,
            'note' => 'October rent',
        ]);

        $response->assertRedirect('/admin/finance/expenses');
        $this->assertDatabaseHas('expenses', [
            'shop_id' => null,
            'category' => 'rent',
            'amount_minor' => 50000_00,
            'note' => 'October rent',
            'recorded_by_staff_id' => $accountant->id,
        ]);
    }

    public function test_an_expense_can_be_scoped_to_a_shop(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $shop = ShopRecord::factory()->create();

        $this->actingAs($owner, 'staff')->post('/admin/finance/expenses', [
            'shop_id' => $shop->id,
            'category' => 'utilities',
            'amount_minor' => 15000_00,
        ])->assertRedirect();

        $this->assertDatabaseHas('expenses', ['shop_id' => $shop->id, 'category' => 'utilities', 'amount_minor' => 15000_00]);
    }

    public function test_rejects_an_unknown_category(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        $response = $this->actingAs($owner, 'staff')
            ->from('/admin/finance/expenses/create')
            ->post('/admin/finance/expenses', ['category' => 'made_up_category', 'amount_minor' => 1000]);

        $response->assertSessionHasErrors('category');
        $this->assertDatabaseMissing('expenses', ['category' => 'made_up_category']);
    }

    public function test_an_expense_can_be_corrected(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        $this->actingAs($owner, 'staff')->post('/admin/finance/expenses', ['category' => 'rent', 'amount_minor' => 1000_00]);
        $expenseId = ExpenseRecord::query()->first()->id;

        $response = $this->actingAs($owner, 'staff')->put("/admin/finance/expenses/{$expenseId}", [
            'category' => 'utilities',
            'amount_minor' => 1200_00,
            'note' => 'Corrected amount',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('expenses', ['id' => $expenseId, 'category' => 'utilities', 'amount_minor' => 1200_00, 'note' => 'Corrected amount']);
    }

    public function test_blocks_a_staff_member_without_expenses_create(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier'); // no expenses.* permissions per config/afprospos.php

        $response = $this->actingAs($cashier, 'staff')->post('/admin/finance/expenses', [
            'category' => 'rent',
            'amount_minor' => 1000_00,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('expenses', ['category' => 'rent']);
    }

    public function test_blocks_a_staff_member_without_expenses_view_from_the_list(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');

        $this->actingAs($cashier, 'staff')->get('/admin/finance/expenses')->assertForbidden();
    }
}
