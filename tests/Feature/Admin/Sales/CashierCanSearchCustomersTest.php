<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Sales;

use Database\Seeders\RolePermissionSeeder;
use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** A cashier picks a customer by name/email at checkout — never by typing a raw customers.id. */
class CashierCanSearchCustomersTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_matches_by_name_or_email_and_never_exposes_a_bare_id_lookup(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');

        $customer = CustomerRecord::factory()->create(['name' => 'Amaka Okafor', 'email' => 'amaka@example.com']);
        CustomerRecord::factory()->create(['name' => 'Someone Else', 'email' => 'someone@example.com']);

        $byName = $this->actingAs($cashier, 'staff')->getJson('/admin/sales/customers/search?q=Amaka');
        $byName->assertOk()->assertJson([
            'results' => [
                ['id' => $customer->id, 'name' => 'Amaka Okafor', 'email' => 'amaka@example.com'],
            ],
        ]);

        $byEmail = $this->actingAs($cashier, 'staff')->getJson('/admin/sales/customers/search?q=amaka@example.com');
        $byEmail->assertOk()->assertJsonCount(1, 'results');

        $noMatch = $this->actingAs($cashier, 'staff')->getJson('/admin/sales/customers/search?q=nobody-matches-this');
        $noMatch->assertOk()->assertJson(['results' => []]);
    }

    public function test_a_staff_member_without_sales_create_cannot_search_customers(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $technician = StaffRecord::factory()->create();
        $technician->assignRole('Technician'); // no sales.create per config/afprospos.php

        $response = $this->actingAs($technician, 'staff')->getJson('/admin/sales/customers/search?q=amaka');

        $response->assertForbidden();
    }
}
