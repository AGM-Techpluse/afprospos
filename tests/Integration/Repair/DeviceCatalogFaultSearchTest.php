<?php

declare(strict_types=1);

namespace Tests\Integration\Repair;

use Domain\Repair\Application\Queries\DeviceCatalogQuery;
use Domain\Repair\Infrastructure\Persistence\Eloquent\DeviceBrandRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\DeviceModelRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\DeviceProblemTagRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\DeviceTypeRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceCatalogFaultSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_faults_ignores_device_scope_entirely(): void
    {
        $phones = DeviceTypeRecord::query()->create(['label' => 'Phones', 'icon' => 'phone', 'sort_order' => 0]);
        $laptops = DeviceTypeRecord::query()->create(['label' => 'Laptops', 'icon' => 'laptop', 'sort_order' => 1]);

        DeviceProblemTagRecord::query()->create(['device_type_id' => $phones->id, 'device_model_id' => null, 'label' => 'Battery drain', 'sort_order' => 0]);
        DeviceProblemTagRecord::query()->create(['device_type_id' => $laptops->id, 'device_model_id' => null, 'label' => 'Battery drain', 'sort_order' => 0]);

        $results = app(DeviceCatalogQuery::class)->searchFaults('battery');

        $this->assertCount(2, $results);
        $this->assertSame(['Phones', 'Laptops'], array_column($results, 'device_type_label'));
    }

    public function test_search_faults_for_model_is_scoped_to_the_models_own_faults_falling_back_to_its_type(): void
    {
        $type = DeviceTypeRecord::query()->create(['label' => 'Phones', 'icon' => 'phone', 'sort_order' => 0]);
        $brand = DeviceBrandRecord::query()->create(['device_type_id' => $type->id, 'name' => 'Google', 'sort_order' => 0]);
        $pixel = DeviceModelRecord::query()->create(['device_brand_id' => $brand->id, 'name' => 'Pixel 8', 'sort_order' => 0]);
        $otherModel = DeviceModelRecord::query()->create(['device_brand_id' => $brand->id, 'name' => 'Pixel 9', 'sort_order' => 1]);

        // Type-level fallback fault — visible to any model of this type with no model-specific override.
        DeviceProblemTagRecord::query()->create(['device_type_id' => $type->id, 'device_model_id' => null, 'label' => 'Charging port', 'sort_order' => 0]);
        // A fault specific to the OTHER model only — must not leak into Pixel 8's results.
        DeviceProblemTagRecord::query()->create(['device_type_id' => $type->id, 'device_model_id' => $otherModel->id, 'label' => 'Camera bump crack', 'sort_order' => 0]);

        $results = app(DeviceCatalogQuery::class)->searchFaultsForModel($pixel->id, null);

        $this->assertCount(1, $results);
        $this->assertSame('Charging port', $results[0]['label']);
    }
}
