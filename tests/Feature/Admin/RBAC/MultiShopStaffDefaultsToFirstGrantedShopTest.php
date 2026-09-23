<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\RBAC;

use Database\Seeders\RolePermissionSeeder;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Before this fix, a non-owner staff member granted 2+ shops had no
 * active shop until they used the switcher — and every list Query
 * treats a null active shop as "no filter, show everything," so they
 * briefly saw every shop's repairs/inventory/sales, not just their own.
 */
class MultiShopStaffDefaultsToFirstGrantedShopTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_multi_shop_staff_member_sees_only_their_own_shops_repairs_on_first_visit(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $shopA = ShopRecord::factory()->create();
        $shopB = ShopRecord::factory()->create();
        $shopC = ShopRecord::factory()->create();

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        $this->grantShopAccess($cashier, $shopA->id);
        $this->grantShopAccess($cashier, $shopB->id);

        $repairInShopA = RepairJobRecord::factory()->create(['shop_id' => $shopA->id]);
        $repairInShopC = RepairJobRecord::factory()->create(['shop_id' => $shopC->id]);

        $response = $this->actingAs($cashier, 'staff')->get('/admin/repairs');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('filters.shop_id', $shopA->id)
            ->where('repairs.data.0.id', $repairInShopA->id)
            ->where('repairs.total', 1));

        $this->assertDatabaseMissing('repair_jobs', ['id' => $repairInShopC->id, 'shop_id' => $shopA->id]);
    }

    public function test_a_single_shop_staff_member_cannot_see_another_shops_repair_via_an_explicit_all_shops_filter(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $ownShop = ShopRecord::factory()->create();
        $otherShop = ShopRecord::factory()->create();

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        $this->grantShopAccess($cashier, $ownShop->id);

        RepairJobRecord::factory()->create(['shop_id' => $ownShop->id]);
        RepairJobRecord::factory()->create(['shop_id' => $otherShop->id]);

        // shop_id='' is how the Index page's "All shops" filter option posts.
        $response = $this->actingAs($cashier, 'staff')->get('/admin/repairs?shop_id=');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->where('repairs.total', 1));
    }
}
