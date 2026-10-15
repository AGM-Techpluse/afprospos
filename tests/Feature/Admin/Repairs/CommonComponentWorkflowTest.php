<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Repairs;

use Database\Seeders\RolePermissionSeeder;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CommonComponentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_with_repairs_manage_can_manage_the_common_component_catalog(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        $storeResponse = $this->actingAs($owner, 'staff')->post('/admin/settings/common-components', [
            'label' => 'Battery',
            'sort_order' => 0,
            'commission_amount_minor' => 50000,
        ]);
        $storeResponse->assertRedirect();
        $this->assertDatabaseHas('common_components', ['label' => 'Battery', 'commission_amount_minor' => 50000]);
        $componentId = DB::table('common_components')->where('label', 'Battery')->value('id');

        $updateResponse = $this->actingAs($owner, 'staff')->put("/admin/settings/common-components/{$componentId}", [
            'label' => 'Battery pack',
            'sort_order' => 1,
            'commission_amount_minor' => 75000,
        ]);
        $updateResponse->assertRedirect();
        $this->assertDatabaseHas('common_components', ['id' => $componentId, 'label' => 'Battery pack', 'commission_amount_minor' => 75000]);

        $indexResponse = $this->actingAs($owner, 'staff')->get('/admin/settings/common-components');
        $indexResponse->assertOk();
        $indexResponse->assertInertia(fn ($page) => $page
            ->component('Admin/Settings/CommonComponents/Index')
            ->where('components.0.label', 'Battery pack')
            ->where('components.0.commission_amount_minor', 75000));

        $destroyResponse = $this->actingAs($owner, 'staff')->delete("/admin/settings/common-components/{$componentId}");
        $destroyResponse->assertRedirect();
        $this->assertDatabaseMissing('common_components', ['id' => $componentId]);

        $this->assertDatabaseHas('audit_logs', ['module' => 'Repair', 'event_type' => 'CommonComponentCreated']);
        $this->assertDatabaseHas('audit_logs', ['module' => 'Repair', 'event_type' => 'CommonComponentUpdated']);
        $this->assertDatabaseHas('audit_logs', ['module' => 'Repair', 'event_type' => 'CommonComponentDeleted']);
    }

    public function test_a_staff_member_without_repairs_manage_cannot_edit_the_common_component_catalog(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier'); // no repairs.manage per config/afprospos.php

        $response = $this->actingAs($cashier, 'staff')->post('/admin/settings/common-components', [
            'label' => 'Battery',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('common_components', ['label' => 'Battery']);
    }
}
