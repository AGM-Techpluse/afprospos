<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Repairs;

use Database\Seeders\RolePermissionSeeder;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
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

    public function test_a_walk_in_repairs_contact_details_create_a_findable_customer_record(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        $shop = ShopRecord::factory()->create();
        $this->grantShopAccess($cashier, $shop->id);

        $response = $this->actingAs($cashier, 'staff')->post('/admin/repairs', [
            'shop_id' => $shop->id,
            'customer_id' => '',
            'walk_in_name' => 'Kemi Olatunde',
            'walk_in_phone' => '08066667777',
            'walk_in_email' => 'kemi@example.com',
            'device_make' => 'Samsung',
            'device_model' => 'Galaxy A54',
            'reported_issue' => 'Battery drains fast',
            'device_lock_type' => 'none',
        ]);

        $response->assertSessionHasNoErrors();
        // The repair job itself is untouched — still a plain walk-in record, not silently repointed.
        $this->assertDatabaseHas('repair_jobs', ['device_model' => 'Galaxy A54', 'customer_id' => null]);
        // But the person is now findable on a later visit.
        $customer = CustomerRecord::query()->where('phone', '08066667777')->first();
        $this->assertNotNull($customer);
        $this->assertSame('Kemi Olatunde', $customer->name);
        $this->assertNull($customer->password);
    }
}
