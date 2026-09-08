<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\RBAC;

use Database\Seeders\RolePermissionSeeder;
use Domain\RBAC\Infrastructure\Persistence\Eloquent\StaffShopGrantRecord;
use Domain\Shop\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffCannotAccessAnotherShopTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Requires the guard-aware exception mapping described in the Phase 1
     * guide (bootstrap/app.php `->withExceptions()`: ShopScopeViolation -> 403).
     */
    public function test_blocks_a_staff_member_from_switching_into_a_shop_they_were_never_granted(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $shopA = ShopRecord::factory()->create();
        $shopB = ShopRecord::factory()->create();

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');

        StaffShopGrantRecord::query()->create([
            'staff_id' => $cashier->id,
            'shop_id' => $shopA->id,
            'granted_by_staff_id' => null,
            'granted_at' => now(),
        ]);

        $response = $this->actingAs($cashier, 'staff')
            ->post('/admin/shops/switch', ['shop_id' => $shopB->id]);

        $response->assertForbidden();
    }
}
