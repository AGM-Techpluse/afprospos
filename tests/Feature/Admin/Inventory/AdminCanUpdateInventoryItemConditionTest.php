<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Inventory;

use Database\Seeders\RolePermissionSeeder;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\InventoryItemRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Corrects a serialized unit's condition grading after intake — e.g. a unit entered before a condition existed in the catalog, or graded wrong. */
class AdminCanUpdateInventoryItemConditionTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_can_change_a_units_condition_to_a_catalog_value(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $unit = InventoryItemRecord::factory()->create(['condition' => 'new']);

        $response = $this->actingAs($owner, 'staff')->post("/admin/inventory/serialized-units/{$unit->id}/condition", [
            'condition' => 'used_grade_b',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('inventory_items', ['id' => $unit->id, 'condition' => 'used_grade_b']);
    }

    public function test_a_unit_can_be_corrected_to_a_condition_added_after_it_was_received(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        // Simulates the exact scenario this feature exists for: a unit
        // received with a value ('open_box') before that condition was
        // ever added to the admin-managed catalog.
        $unit = InventoryItemRecord::factory()->create(['condition' => 'open_box']);

        $this->actingAs($owner, 'staff')->post('/admin/inventory/masters', [
            'kind' => 'condition',
            'name' => 'open_box',
        ])->assertRedirect();

        $response = $this->actingAs($owner, 'staff')->post("/admin/inventory/serialized-units/{$unit->id}/condition", [
            'condition' => 'open_box',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('inventory_items', ['id' => $unit->id, 'condition' => 'open_box']);
    }

    public function test_an_unknown_condition_is_rejected(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $unit = InventoryItemRecord::factory()->create(['condition' => 'new']);

        $response = $this->actingAs($owner, 'staff')
            ->from('/admin/inventory/products/1')
            ->post("/admin/inventory/serialized-units/{$unit->id}/condition", ['condition' => 'not_a_real_condition']);

        $response->assertSessionHasErrors('condition');
        $this->assertDatabaseHas('inventory_items', ['id' => $unit->id, 'condition' => 'new']);
    }

    public function test_blocks_a_staff_member_without_inventory_edit(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        $unit = InventoryItemRecord::factory()->create(['condition' => 'new']);

        $response = $this->actingAs($cashier, 'staff')->post("/admin/inventory/serialized-units/{$unit->id}/condition", [
            'condition' => 'used_grade_a',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('inventory_items', ['id' => $unit->id, 'condition' => 'new']);
    }
}
