<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Repairs;

use Database\Seeders\RolePermissionSeeder;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Cashiers run the repair flow; authorization is the Manager's (and Owner's) decision.
 * A cashier who tries to authorize is refused and the repair is left untouched.
 */
class RepairAuthorizationPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_cashier_can_progress_the_repair_but_not_authorize_it(): void
    {
        $cashier = Role::query()->where('name', 'Cashier')->firstOrFail();

        $this->assertTrue($cashier->hasPermissionTo('repairs.progress'));
        $this->assertFalse($cashier->hasPermissionTo('repairs.authorize'));
    }

    public function test_manager_and_owner_hold_the_authorize_permission(): void
    {
        $this->assertTrue(Role::query()->where('name', 'Manager')->firstOrFail()->hasPermissionTo('repairs.authorize'));
        $this->assertTrue(Role::query()->where('name', 'Shop Owner')->firstOrFail()->hasPermissionTo('repairs.authorize'));
    }

    public function test_a_cashier_who_tries_to_authorize_is_refused_and_the_repair_is_unchanged(): void
    {
        $shop = ShopRecord::factory()->create();
        $repair = RepairJobRecord::factory()->create(['shop_id' => $shop->id, 'repair_status' => 'awaiting_authorization']);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');

        $this->actingAs($cashier, 'staff')
            ->post("/admin/repairs/{$repair->id}/authorize", ['down_payment_required_minor' => 0])
            ->assertForbidden();

        $this->assertDatabaseHas('repair_jobs', ['id' => $repair->id, 'repair_status' => 'awaiting_authorization']);
    }
}
