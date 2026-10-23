<?php

declare(strict_types=1);

namespace Tests\Integration\Repair;

use Domain\Repair\Application\Commands\AddFixedFaultToCatalogCommand;
use Domain\Repair\Application\Handlers\AddFixedFaultToCatalogHandler;
use Domain\Repair\Domain\Exceptions\RepairHasNoDeviceModel;
use Domain\Repair\Infrastructure\Persistence\Eloquent\DeviceBrandRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\DeviceModelRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\DeviceTypeRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddFixedFaultToCatalogHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_fault_joins_the_catalog_scoped_to_the_repairs_own_device_model_with_commission(): void
    {
        $staff = StaffRecord::factory()->create();
        $shop = ShopRecord::factory()->create();
        $type = DeviceTypeRecord::query()->create(['label' => 'Phones', 'icon' => 'phone', 'sort_order' => 0]);
        $brand = DeviceBrandRecord::query()->create(['device_type_id' => $type->id, 'name' => 'Google', 'sort_order' => 0]);
        $model = DeviceModelRecord::query()->create(['device_brand_id' => $brand->id, 'name' => 'Pixel 8', 'sort_order' => 0]);
        $job = RepairJobRecord::factory()->create(['shop_id' => $shop->id, 'device_model_id' => $model->id]);

        $result = app(AddFixedFaultToCatalogHandler::class)->handle(new AddFixedFaultToCatalogCommand(
            repairJobId: $job->id,
            label: 'Loose hinge',
            commissionAmountMinor: 75000,
            addedByStaffId: $staff->id,
        ));

        $this->assertSame('Loose hinge', $result['label']);
        $this->assertSame(75000, $result['commission_amount_minor']);
        $this->assertDatabaseHas('device_problem_tags', [
            'id' => $result['id'],
            'device_model_id' => $model->id,
            'commission_amount_minor' => 75000,
        ]);
    }

    public function test_a_repair_with_no_device_model_cannot_add_a_catalog_fault(): void
    {
        $job = RepairJobRecord::factory()->create(['device_model_id' => null]);
        $staff = StaffRecord::factory()->create();

        $this->expectException(RepairHasNoDeviceModel::class);

        app(AddFixedFaultToCatalogHandler::class)->handle(new AddFixedFaultToCatalogCommand(
            repairJobId: $job->id,
            label: 'Something',
            commissionAmountMinor: null,
            addedByStaffId: $staff->id,
        ));
    }
}
