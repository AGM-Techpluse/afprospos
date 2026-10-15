<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Collection;

use Database\Seeders\RolePermissionSeeder;
use Domain\Collection\Infrastructure\Persistence\Eloquent\CollectionCaseRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CollectionTransferTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_transfer_pickup_without_changing_originating_shop(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $originShop = ShopRecord::factory()->create();
        $pickupShop = ShopRecord::factory()->create();
        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        $this->grantShopAccess($cashier, $originShop->id);
        $this->grantShopAccess($cashier, $pickupShop->id);

        $case = CollectionCaseRecord::factory()->create([
            'shop_id' => $originShop->id,
            'originating_shop_id' => $originShop->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($cashier, 'staff')->post("/admin/collection/{$case->id}/transfer", [
            'target_shop_id' => $pickupShop->id,
            'reason' => 'Customer requested pickup near work',
        ]);

        $response->assertRedirect(route('admin.collection.show', ['collectionCase' => $case->id]));
        $this->assertDatabaseHas('collection_cases', [
            'id' => $case->id,
            'shop_id' => $pickupShop->id,
            'originating_shop_id' => $originShop->id,
        ]);
        $this->assertDatabaseHas('collection_case_events', [
            'collection_case_id' => $case->id,
            'event_type' => 'collection_transferred',
            'actor_staff_id' => $cashier->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'Collection',
            'event_type' => 'CollectionCaseTransferred',
            'subject_type' => 'collection_case',
            'subject_id' => $case->id,
        ]);
    }

    public function test_collection_transfer_requires_access_to_target_shop(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $originShop = ShopRecord::factory()->create();
        $pickupShop = ShopRecord::factory()->create();
        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        $this->grantShopAccess($cashier, $originShop->id);

        $case = CollectionCaseRecord::factory()->create([
            'shop_id' => $originShop->id,
            'originating_shop_id' => $originShop->id,
        ]);

        $response = $this->actingAs($cashier, 'staff')->post("/admin/collection/{$case->id}/transfer", [
            'target_shop_id' => $pickupShop->id,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('collection_cases', ['id' => $case->id, 'shop_id' => $originShop->id]);
    }

    public function test_resolved_collection_case_cannot_be_transferred(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $originShop = ShopRecord::factory()->create();
        $pickupShop = ShopRecord::factory()->create();
        $manager = StaffRecord::factory()->create();
        $manager->assignRole('Manager');
        $this->grantShopAccess($manager, $originShop->id);
        $this->grantShopAccess($manager, $pickupShop->id);

        $case = CollectionCaseRecord::factory()->create([
            'shop_id' => $originShop->id,
            'originating_shop_id' => $originShop->id,
            'status' => 'resolved',
        ]);

        $response = $this->actingAs($manager, 'staff')->post("/admin/collection/{$case->id}/transfer", [
            'target_shop_id' => $pickupShop->id,
        ]);

        $response->assertSessionHasErrors('target_shop_id');
        $this->assertDatabaseHas('collection_cases', ['id' => $case->id, 'shop_id' => $originShop->id]);
    }
}
