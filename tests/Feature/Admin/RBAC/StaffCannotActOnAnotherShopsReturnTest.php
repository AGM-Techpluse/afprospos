<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\RBAC;

use Database\Seeders\RolePermissionSeeder;
use Domain\Sales\Infrastructure\Persistence\Eloquent\SaleRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Domain\Warranty\Infrastructure\Persistence\Eloquent\ReturnRequestRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** BRD RBAC-05: a return request's shop is derived from its originating sale — granted access to that sale's shop is required, not just the `warranty.*` permission string. */
class StaffCannotActOnAnotherShopsReturnTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_technician_granted_only_shop_a_cannot_assess_a_return_from_shop_bs_sale(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $shopA = ShopRecord::factory()->create();
        $shopB = ShopRecord::factory()->create();

        $technician = StaffRecord::factory()->create();
        $technician->assignRole('Technician');
        $this->grantShopAccess($technician, $shopA->id);

        $sale = SaleRecord::factory()->create(['shop_id' => $shopB->id]);
        $returnRequest = ReturnRequestRecord::factory()->create([
            'sale_id' => $sale->id,
            'return_window_expires_at' => now()->addDays(5),
        ]);

        $response = $this->actingAs($technician, 'staff')->post("/admin/warranty/returns/{$returnRequest->id}/assess");

        $response->assertForbidden();
        $this->assertDatabaseHas('return_requests', ['id' => $returnRequest->id, 'resolution_state' => 'requested']);
    }
}
