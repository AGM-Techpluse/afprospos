<?php

declare(strict_types=1);

namespace Tests\Integration\Repair;

use Domain\Repair\Application\Commands\AddRevealedFaultCommand;
use Domain\Repair\Application\Handlers\AddRevealedFaultHandler;
use Domain\Repair\Infrastructure\Persistence\Eloquent\DeviceBrandRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\DeviceModelRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\DeviceProblemTagRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\DeviceTypeRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\DiagnosisCaseRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddRevealedFaultHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_label_joins_the_catalog_at_the_diagnosing_devices_type_level(): void
    {
        $staff = StaffRecord::factory()->create();
        $shop = ShopRecord::factory()->create();
        $type = DeviceTypeRecord::query()->create(['label' => 'Phones', 'icon' => 'phone', 'sort_order' => 0]);
        $brand = DeviceBrandRecord::query()->create(['device_type_id' => $type->id, 'name' => 'Google', 'sort_order' => 0]);
        $model = DeviceModelRecord::query()->create(['device_brand_id' => $brand->id, 'name' => 'Pixel 8', 'sort_order' => 0]);
        $case = DiagnosisCaseRecord::query()->create(['shop_id' => $shop->id, 'device_model_id' => $model->id, 'device_make' => 'Google', 'device_model' => 'Pixel 8', 'status' => 'received', 'created_by_staff_id' => $staff->id]);

        $result = app(AddRevealedFaultHandler::class)->handle(new AddRevealedFaultCommand(
            diagnosisCaseId: $case->id,
            label: 'Speaker crackling',
            addedByStaffId: $staff->id,
        ));

        $this->assertNotNull($result);
        $this->assertSame('Speaker crackling', $result['label']);
        $this->assertDatabaseHas('device_problem_tags', [
            'id' => $result['id'],
            'device_type_id' => $type->id,
            'device_model_id' => null,
            'label' => 'Speaker crackling',
        ]);
    }

    public function test_reuses_an_existing_tag_of_the_same_label_at_the_same_type_instead_of_duplicating(): void
    {
        $staff = StaffRecord::factory()->create();
        $shop = ShopRecord::factory()->create();
        $type = DeviceTypeRecord::query()->create(['label' => 'Phones', 'icon' => 'phone', 'sort_order' => 0]);
        $brand = DeviceBrandRecord::query()->create(['device_type_id' => $type->id, 'name' => 'Google', 'sort_order' => 0]);
        $model = DeviceModelRecord::query()->create(['device_brand_id' => $brand->id, 'name' => 'Pixel 8', 'sort_order' => 0]);
        $case = DiagnosisCaseRecord::query()->create(['shop_id' => $shop->id, 'device_model_id' => $model->id, 'device_make' => 'Google', 'device_model' => 'Pixel 8', 'status' => 'received', 'created_by_staff_id' => $staff->id]);
        $existingTag = DeviceProblemTagRecord::query()->create(['device_type_id' => $type->id, 'device_model_id' => null, 'label' => 'Speaker crackling', 'sort_order' => 0]);

        $result = app(AddRevealedFaultHandler::class)->handle(new AddRevealedFaultCommand(
            diagnosisCaseId: $case->id,
            label: 'Speaker crackling',
            addedByStaffId: $staff->id,
        ));

        $this->assertSame($existingTag->id, $result['id']);
        $this->assertSame(1, DeviceProblemTagRecord::query()->where('label', 'Speaker crackling')->count());
    }
}
