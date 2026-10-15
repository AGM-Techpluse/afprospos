<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Repairs;

use Database\Seeders\RolePermissionSeeder;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The customer is optional at intake: a walk-in repair (no customer on record) is accepted and saved. */
class WalkInRepairIntakeTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_repair_can_be_received_without_a_customer(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        $shop = ShopRecord::factory()->create();
        $this->grantShopAccess($cashier, $shop->id);

        $response = $this->actingAs($cashier, 'staff')->post('/admin/repairs', [
            'shop_id' => $shop->id,
            'customer_id' => '',
            'device_make' => 'Google',
            'device_model' => 'Pixel 7',
            'reported_issue' => 'Cracked screen',
            'device_lock_type' => 'none',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $this->assertDatabaseHas('repair_jobs', ['shop_id' => $shop->id, 'device_model' => 'Pixel 7', 'customer_id' => null]);
        $this->assertSame(1, RepairJobRecord::query()->count());
    }
}
