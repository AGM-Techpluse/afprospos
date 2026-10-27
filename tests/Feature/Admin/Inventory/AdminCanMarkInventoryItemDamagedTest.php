<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Inventory;

use Database\Seeders\RolePermissionSeeder;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryItemRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCanMarkInventoryItemDamagedTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_available_unit_can_be_marked_damaged(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $unit = InventoryItemRecord::factory()->create(['status' => 'available']);

        $response = $this->actingAs($owner, 'staff')->post("/admin/inventory/serialized-units/{$unit->id}/status", [
            'to_status' => 'damaged',
            'note' => 'Cracked screen found during intake check',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('inventory_items', ['id' => $unit->id, 'status' => 'damaged']);
    }

    public function test_a_damaged_unit_can_be_restored_to_available(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $unit = InventoryItemRecord::factory()->create(['status' => 'damaged']);

        $response = $this->actingAs($owner, 'staff')->post("/admin/inventory/serialized-units/{$unit->id}/status", [
            'to_status' => 'available',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('inventory_items', ['id' => $unit->id, 'status' => 'available']);
    }

    public function test_a_reserved_unit_cannot_be_marked_damaged(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $unit = InventoryItemRecord::factory()->reserved()->create();

        $response = $this->actingAs($owner, 'staff')
            ->from('/admin/inventory/serialized-units')
            ->post("/admin/inventory/serialized-units/{$unit->id}/status", ['to_status' => 'damaged']);

        $response->assertSessionHasErrors('to_status');
        $this->assertDatabaseHas('inventory_items', ['id' => $unit->id, 'status' => 'reserved']);
    }

    public function test_blocks_a_staff_member_without_inventory_edit(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        $unit = InventoryItemRecord::factory()->create(['status' => 'available']);

        $response = $this->actingAs($cashier, 'staff')->post("/admin/inventory/serialized-units/{$unit->id}/status", [
            'to_status' => 'damaged',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('inventory_items', ['id' => $unit->id, 'status' => 'available']);
    }
}
