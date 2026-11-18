<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Repairs;

use Database\Seeders\RolePermissionSeeder;
use Domain\Repair\Infrastructure\Persistence\Eloquent\DeviceBrandRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\DeviceModelRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\DeviceTypeRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DiagnosisCaseTechnicianOptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_technician_options_follow_the_selected_shop(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $shopA = ShopRecord::factory()->create();
        $shopB = ShopRecord::factory()->create();
        $technicianA = StaffRecord::factory()->create(['name' => 'Technician A']);
        $technicianA->assignRole('Technician');
        $technicianB = StaffRecord::factory()->create(['name' => 'Technician B']);
        $technicianB->assignRole('Technician');
        $this->grantShopAccess($technicianA, $shopA->id);
        $this->grantShopAccess($technicianB, $shopB->id);

        $this->actingAs($owner, 'staff')
            ->getJson("/admin/repairs/diagnosis-cases/technicians?shop_id={$shopB->id}")
            ->assertOk()
            ->assertJsonCount(1, 'technicians')
            ->assertJsonPath('technicians.0.id', $technicianB->id)
            ->assertJsonMissing(['id' => $technicianA->id]);
    }

    public function test_a_diagnosis_cannot_assign_a_technician_from_another_shop(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $shopA = ShopRecord::factory()->create();
        $shopB = ShopRecord::factory()->create();
        $technician = StaffRecord::factory()->create();
        $technician->assignRole('Technician');
        $this->grantShopAccess($technician, $shopB->id);

        $deviceType = DeviceTypeRecord::query()->create(['label' => 'Phone', 'icon' => 'phone', 'sort_order' => 1]);
        $brand = DeviceBrandRecord::query()->create(['device_type_id' => $deviceType->id, 'name' => 'Apple', 'sort_order' => 0]);
        $model = DeviceModelRecord::query()->create(['device_brand_id' => $brand->id, 'name' => 'iPhone 15', 'sort_order' => 0]);

        $this->actingAs($owner, 'staff')
            ->from('/admin/repairs/diagnosis-cases/create')
            ->post('/admin/repairs/diagnosis-cases', [
                'shop_id' => $shopA->id,
                'device_model_id' => $model->id,
                'diagnosis_technician_staff_id' => $technician->id,
                'device_make' => 'Apple',
                'device_model' => 'iPhone 15',
                'device_lock_type' => 'none',
            ])
            ->assertSessionHasErrors('diagnosis_technician_staff_id');

        $this->assertDatabaseCount('diagnosis_cases', 0);
    }
}
