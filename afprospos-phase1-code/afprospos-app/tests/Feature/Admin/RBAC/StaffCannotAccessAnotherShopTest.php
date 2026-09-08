<?php

declare(strict_types=1);

use Database\Seeders\RolePermissionSeeder;
use Domain\RBAC\Infrastructure\Persistence\Eloquent\StaffShopGrantRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Requires the guard-aware exception mapping described in the Phase 1
 * guide (bootstrap/app.php `->withExceptions()`: ShopScopeViolation -> 403).
 */
it('blocks a staff member from switching into a shop they were never granted', function (): void {
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
});
