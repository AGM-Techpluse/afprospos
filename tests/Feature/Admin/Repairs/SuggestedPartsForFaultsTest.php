<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Repairs;

use Database\Seeders\RolePermissionSeeder;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\Repair\Application\Commands\AttachSuggestedPartCommand;
use Domain\Repair\Application\Commands\CreateDeviceProblemTagCommand;
use Domain\Repair\Application\Commands\CreateDeviceTypeCommand;
use Domain\Repair\Application\Handlers\AttachSuggestedPartHandler;
use Domain\Repair\Application\Handlers\CreateDeviceProblemTagHandler;
use Domain\Repair\Application\Handlers\CreateDeviceTypeHandler;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The "actually fixed" picker's locally-selected faults aren't saved
 * anywhere until "Complete repair" is submitted, so this endpoint reads
 * suggestions live off whatever tag ids the frontend already has picked
 * — not off any saved repair state.
 */
class SuggestedPartsForFaultsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_suggested_parts_for_the_given_tag_ids(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $shop = ShopRecord::factory()->create();
        $staff = StaffRecord::factory()->create();
        $staff->assignRole('Technician');
        $this->grantShopAccess($staff, $shop->id);

        $job = RepairJobRecord::factory()->create(['shop_id' => $shop->id]);
        $screenSku = SkuRecord::factory()->nonSerialized()->create();

        $typeId = app(CreateDeviceTypeHandler::class)->handle(new CreateDeviceTypeCommand('Smartphones', 'phone', 0, $staff->id));
        $screenTagId = app(CreateDeviceProblemTagHandler::class)->handle(new CreateDeviceProblemTagCommand(
            deviceTypeId: $typeId,
            deviceModelId: null,
            label: 'Screen',
            sortOrder: 0,
            createdByStaffId: $staff->id,
        ));
        app(AttachSuggestedPartHandler::class)->handle(new AttachSuggestedPartCommand($screenTagId, $screenSku->id, $staff->id));

        $response = $this->actingAs($staff, 'staff')
            ->get("/admin/repairs/{$job->id}/faults/suggested-parts?tag_ids[]={$screenTagId}");

        $response->assertOk();
        $response->assertJson(['results' => [['sku_id' => $screenSku->id, 'sku_code' => $screenSku->sku_code, 'product_name' => $screenSku->product->brand.' '.$screenSku->product->model]]]);
    }

    public function test_no_tag_ids_returns_an_empty_list(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $shop = ShopRecord::factory()->create();
        $staff = StaffRecord::factory()->create();
        $staff->assignRole('Technician');
        $this->grantShopAccess($staff, $shop->id);

        $job = RepairJobRecord::factory()->create(['shop_id' => $shop->id]);

        $response = $this->actingAs($staff, 'staff')->get("/admin/repairs/{$job->id}/faults/suggested-parts");

        $response->assertOk();
        $response->assertJson(['results' => []]);
    }

    public function test_a_staff_member_without_access_to_the_repairs_shop_is_forbidden(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $shop = ShopRecord::factory()->create();
        $staff = StaffRecord::factory()->create();
        $staff->assignRole('Technician');
        // Deliberately no grantShopAccess() — this staff member has no access to $shop.

        $job = RepairJobRecord::factory()->create(['shop_id' => $shop->id]);

        $response = $this->actingAs($staff, 'staff')->get("/admin/repairs/{$job->id}/faults/suggested-parts");

        $response->assertForbidden();
    }
}
