<?php

declare(strict_types=1);

namespace Tests\Integration\Shop;

use Domain\Repair\Application\Commands\CreateRepairJobCommand;
use Domain\Repair\Application\Handlers\CreateRepairJobHandler;
use Domain\Repair\Domain\Exceptions\WarehouseCannotTakeRepairs;
use Domain\Sales\Application\Commands\CreateCheckoutCommand;
use Domain\Sales\Application\Handlers\CreateCheckoutHandler;
use Domain\Sales\Domain\Exceptions\WarehouseCannotSell;
use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Domain\Shop\Application\Queries\AccessibleShopsQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A warehouse holds and transfers stock; it never takes a sale or a repair intake.
 * Shops keep both abilities.
 */
class WarehouseIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_warehouse_cannot_open_a_checkout(): void
    {
        $warehouse = ShopRecord::factory()->create(['is_warehouse' => true]);
        $cashier = StaffRecord::factory()->create();

        $this->expectException(WarehouseCannotSell::class);

        app(CreateCheckoutHandler::class)->handle(new CreateCheckoutCommand(
            shopId: $warehouse->id,
            customerId: null,
            cashierStaffId: $cashier->id,
        ));
    }

    public function test_a_warehouse_cannot_take_a_repair_intake_and_nothing_is_saved(): void
    {
        $warehouse = ShopRecord::factory()->create(['is_warehouse' => true]);
        $customer = CustomerRecord::factory()->create();
        $staff = StaffRecord::factory()->create();

        try {
            app(CreateRepairJobHandler::class)->handle($this->intake($warehouse->id, $customer->id, $staff->id));
            $this->fail('Expected WarehouseCannotTakeRepairs to be thrown.');
        } catch (WarehouseCannotTakeRepairs) {
            // expected
        }

        $this->assertDatabaseCount('repair_jobs', 0);
    }

    public function test_a_normal_shop_still_takes_checkouts_and_repairs(): void
    {
        $shop = ShopRecord::factory()->create(['is_warehouse' => false]);
        $customer = CustomerRecord::factory()->create();
        $staff = StaffRecord::factory()->create();

        $checkoutId = app(CreateCheckoutHandler::class)->handle(new CreateCheckoutCommand(
            shopId: $shop->id,
            customerId: null,
            cashierStaffId: $staff->id,
        ));

        $repairId = app(CreateRepairJobHandler::class)->handle($this->intake($shop->id, $customer->id, $staff->id));

        $this->assertNotNull($checkoutId);
        $this->assertGreaterThan(0, $repairId);
    }

    public function test_the_shop_switcher_can_tell_a_warehouse_apart_from_a_shop(): void
    {
        $owner = StaffRecord::factory()->create();
        $shop = ShopRecord::factory()->create(['is_warehouse' => false]);
        $warehouse = ShopRecord::factory()->create(['is_warehouse' => true]);

        // isOwner bypasses the shop-grant check, so an owner sees every active shop.
        $accessible = app(AccessibleShopsQuery::class)->forStaff($owner->id, true);
        $byId = collect($accessible)->keyBy('id');

        $this->assertFalse($byId[$shop->id]['is_warehouse']);
        $this->assertTrue($byId[$warehouse->id]['is_warehouse']);
    }

    private function intake(int $shopId, int $customerId, int $staffId): CreateRepairJobCommand
    {
        return new CreateRepairJobCommand(
            shopId: $shopId,
            customerId: $customerId,
            deviceMake: 'Apple',
            deviceModel: 'iPhone 13',
            reportedIssue: 'Cracked screen',
            deviceImeiSerial: null,
            deviceLockType: 'none',
            deviceLockValue: null,
            problemTagIds: [],
            labourChargeMinor: null,
            createdByStaffId: $staffId,
        );
    }
}
