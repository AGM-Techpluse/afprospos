<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Repairs;

use Database\Seeders\RolePermissionSeeder;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\Repair\Domain\Repositories\DeviceProblemSuggestedPartRepository;
use Domain\Repair\Domain\ValueObjects\DeviceProblemTagId;
use Domain\Repair\Infrastructure\Persistence\Eloquent\DeviceProblemTagRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class DeviceCatalogImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_mixed_csv_creates_the_right_hierarchy_and_faults(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        $csv = implode("\n", [
            'device_type,icon,brand,model,fault_label,commission_amount_minor',
            'Smartphones,phone,Apple,iPhone 15 Pro,Screen,300000',
            'Smartphones,phone,Apple,iPhone SE,,',
            'Smartphones,phone,Samsung,,Screen,100000',
        ]);

        $file = UploadedFile::fake()->createWithContent('catalog.csv', $csv);

        $response = $this->actingAs($owner, 'staff')->post('/admin/settings/device-catalog/import', ['file' => $file]);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Settings/DeviceCatalog/Import/Results')
            ->has('results', 3)
            ->where('results.0.succeeded', true)
            ->where('results.1.succeeded', true)
            ->where('results.2.succeeded', true));

        $this->assertDatabaseHas('device_types', ['label' => 'Smartphones', 'icon' => 'phone']);
        $this->assertDatabaseCount('device_types', 1);
        $this->assertDatabaseHas('device_brands', ['name' => 'Apple']);
        $this->assertDatabaseHas('device_brands', ['name' => 'Samsung']);
        $this->assertDatabaseHas('device_models', ['name' => 'iPhone 15 Pro']);
        $this->assertDatabaseHas('device_models', ['name' => 'iPhone SE']);

        $this->assertDatabaseHas('device_problem_tags', [
            'label' => 'Screen',
            'commission_amount_minor' => 300000,
        ]);
        // Samsung's row has no model, so its fault is a type-level default, not model-specific.
        $this->assertDatabaseHas('device_problem_tags', [
            'label' => 'Screen',
            'device_model_id' => null,
            'commission_amount_minor' => 100000,
        ]);
    }

    public function test_reimporting_the_same_file_does_not_duplicate_anything(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        $csv = implode("\n", [
            'device_type,icon,brand,model,fault_label,commission_amount_minor',
            'Smartphones,phone,Apple,iPhone 15 Pro,Screen,300000',
        ]);

        $this->actingAs($owner, 'staff')->post('/admin/settings/device-catalog/import', [
            'file' => UploadedFile::fake()->createWithContent('catalog.csv', $csv),
        ])->assertOk();

        $this->actingAs($owner, 'staff')->post('/admin/settings/device-catalog/import', [
            'file' => UploadedFile::fake()->createWithContent('catalog.csv', $csv),
        ])->assertOk();

        $this->assertDatabaseCount('device_types', 1);
        $this->assertDatabaseCount('device_brands', 1);
        $this->assertDatabaseCount('device_models', 1);
        $this->assertDatabaseCount('device_problem_tags', 1);
    }

    public function test_a_row_missing_device_type_or_brand_fails_without_aborting_the_batch(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        $csv = implode("\n", [
            'device_type,icon,brand,model,fault_label,commission_amount_minor',
            'Smartphones,phone,Apple,iPhone 15 Pro,,',
            ',phone,Apple,iPhone 16,,',
        ]);

        $response = $this->actingAs($owner, 'staff')->post('/admin/settings/device-catalog/import', [
            'file' => UploadedFile::fake()->createWithContent('catalog.csv', $csv),
        ]);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('results', 2)
            ->where('results.0.succeeded', true)
            ->where('results.1.succeeded', false)
            ->whereNot('results.1.error_message', null));

        $this->assertDatabaseHas('device_models', ['name' => 'iPhone 15 Pro']);
        $this->assertDatabaseMissing('device_models', ['name' => 'iPhone 16']);
    }

    public function test_a_fault_row_attaches_its_suggested_parts_by_sku_code(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        $screenSku = SkuRecord::factory()->nonSerialized()->create(['sku_code' => 'IPH15PRO-SCR']);
        $batterySku = SkuRecord::factory()->nonSerialized()->create(['sku_code' => 'IPH15PRO-BAT']);

        $csv = implode("\n", [
            'device_type,icon,brand,model,fault_label,commission_amount_minor,suggested_part_skus',
            'Smartphones,phone,Apple,iPhone 15 Pro,Screen,300000,"IPH15PRO-SCR;IPH15PRO-BAT;UNKNOWN-CODE"',
        ]);

        $response = $this->actingAs($owner, 'staff')->post('/admin/settings/device-catalog/import', [
            'file' => UploadedFile::fake()->createWithContent('catalog.csv', $csv),
        ]);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('results.0.succeeded', true)
            ->where('results.0.error_message', null));

        $tagId = (int) DeviceProblemTagRecord::query()->where('label', 'Screen')->value('id');

        $suggested = app(DeviceProblemSuggestedPartRepository::class)->forProblemTag(new DeviceProblemTagId($tagId));
        $suggestedSkuIds = array_column($suggested, 'sku_id');

        $this->assertCount(2, $suggested);
        $this->assertContains($screenSku->id, $suggestedSkuIds);
        $this->assertContains($batterySku->id, $suggestedSkuIds);
    }

    public function test_reimporting_suggested_parts_does_not_duplicate_the_link(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        $sku = SkuRecord::factory()->nonSerialized()->create(['sku_code' => 'IPH15PRO-SCR']);

        $csv = implode("\n", [
            'device_type,icon,brand,model,fault_label,commission_amount_minor,suggested_part_skus',
            'Smartphones,phone,Apple,iPhone 15 Pro,Screen,300000,IPH15PRO-SCR',
        ]);

        $this->actingAs($owner, 'staff')->post('/admin/settings/device-catalog/import', [
            'file' => UploadedFile::fake()->createWithContent('catalog.csv', $csv),
        ])->assertOk();

        $this->actingAs($owner, 'staff')->post('/admin/settings/device-catalog/import', [
            'file' => UploadedFile::fake()->createWithContent('catalog.csv', $csv),
        ])->assertOk();

        $this->assertDatabaseCount('device_problem_suggested_skus', 1);
    }
}
