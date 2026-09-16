<?php

declare(strict_types=1);

namespace Tests\Integration\Repair;

use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\Repair\Application\Commands\AttachSuggestedPartCommand;
use Domain\Repair\Application\Commands\CreateDeviceProblemTagCommand;
use Domain\Repair\Application\Commands\CreateDeviceTypeCommand;
use Domain\Repair\Application\Commands\CreateRepairJobCommand;
use Domain\Repair\Application\Handlers\AttachSuggestedPartHandler;
use Domain\Repair\Application\Handlers\CreateDeviceProblemTagHandler;
use Domain\Repair\Application\Handlers\CreateDeviceTypeHandler;
use Domain\Repair\Application\Handlers\CreateRepairJobHandler;
use Domain\Repair\Application\Queries\DeviceCatalogQuery;
use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** A repair job has no device_type_id of its own — suggestions are derived by joining its selected problem tags (repair_job_problem_tags) back to the catalog's suggested-SKU mapping. */
class SuggestedPartsForRepairTest extends TestCase
{
    use RefreshDatabase;

    public function test_suggested_parts_are_derived_from_the_repairs_selected_problem_tags(): void
    {
        $shop = ShopRecord::factory()->create();
        $customer = CustomerRecord::factory()->create();
        $staff = StaffRecord::factory()->create();
        $screenSku = SkuRecord::factory()->nonSerialized()->create();
        $batterySku = SkuRecord::factory()->nonSerialized()->create();
        $unrelatedSku = SkuRecord::factory()->nonSerialized()->create();

        $typeId = app(CreateDeviceTypeHandler::class)->handle(new CreateDeviceTypeCommand('Smartphones', 'phone', 0, $staff->id));
        $screenTagId = app(CreateDeviceProblemTagHandler::class)->handle(new CreateDeviceProblemTagCommand($typeId, 'Screen', 0, $staff->id));
        $batteryTagId = app(CreateDeviceProblemTagHandler::class)->handle(new CreateDeviceProblemTagCommand($typeId, 'Battery', 1, $staff->id));

        app(AttachSuggestedPartHandler::class)->handle(new AttachSuggestedPartCommand($screenTagId, $screenSku->id, $staff->id));
        app(AttachSuggestedPartHandler::class)->handle(new AttachSuggestedPartCommand($batteryTagId, $batterySku->id, $staff->id));

        $jobId = app(CreateRepairJobHandler::class)->handle(new CreateRepairJobCommand(
            shopId: $shop->id,
            customerId: $customer->id,
            deviceMake: 'Apple',
            deviceModel: 'iPhone 12',
            reportedIssue: 'Screen cracked',
            deviceImeiSerial: null,
            deviceLockType: 'none',
            deviceLockValue: null,
            problemTagIds: [$screenTagId],
            labourChargeMinor: 500000,
            createdByStaffId: $staff->id,
        ));

        $suggestions = app(DeviceCatalogQuery::class)->suggestedPartsForRepair($jobId, $shop->id);

        $this->assertCount(1, $suggestions);
        $this->assertSame($screenSku->id, $suggestions[0]['sku_id']);
        $this->assertNotContains($batterySku->id, array_column($suggestions, 'sku_id'));
        $this->assertNotContains($unrelatedSku->id, array_column($suggestions, 'sku_id'));
    }
}
