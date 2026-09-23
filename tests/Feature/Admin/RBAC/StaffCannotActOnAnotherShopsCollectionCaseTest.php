<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\RBAC;

use Database\Seeders\RolePermissionSeeder;
use Domain\Collection\Infrastructure\Persistence\Eloquent\CollectionCaseRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** BRD RBAC-05: collection-case actions are blocked unless the acting staff was granted that case's shop. */
class StaffCannotActOnAnotherShopsCollectionCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_technician_granted_only_shop_a_cannot_notify_a_shop_bs_collection_case(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $shopA = ShopRecord::factory()->create();
        $shopB = ShopRecord::factory()->create();

        $technician = StaffRecord::factory()->create();
        $technician->assignRole('Technician');
        $this->grantShopAccess($technician, $shopA->id);

        $case = CollectionCaseRecord::factory()->create(['shop_id' => $shopB->id]);

        $response = $this->actingAs($technician, 'staff')->post("/admin/collection/{$case->id}/notify", ['note' => 'Called customer']);

        $response->assertForbidden();
        $this->assertDatabaseMissing('collection_case_events', ['collection_case_id' => $case->id, 'event_type' => 'notified']);
    }
}
