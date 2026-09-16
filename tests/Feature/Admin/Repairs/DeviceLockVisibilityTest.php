<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Repairs;

use Database\Seeders\RolePermissionSeeder;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** The device lock code/pattern is customer credential data — only the assigned technician or the Shop Owner should ever see the decrypted value back from the Show page. */
class DeviceLockVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_assigned_technician_can_see_the_decrypted_device_lock(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $technician = StaffRecord::factory()->create();
        $technician->assignRole('Technician');
        $shop = ShopRecord::factory()->create();
        $customer = CustomerRecord::factory()->create();

        $job = RepairJobRecord::factory()->create([
            'shop_id' => $shop->id,
            'customer_id' => $customer->id,
            'technician_staff_id' => $technician->id,
            'device_lock_type' => 'code',
            'device_lock_value' => '7890',
        ]);

        $response = $this->actingAs($technician, 'staff')->get("/admin/repairs/{$job->id}");

        $response->assertInertia(fn ($page) => $page
            ->where('repair.device_lock_visible_to_you', true)
            ->where('repair.device_lock_value', '7890'));
    }

    public function test_the_shop_owner_can_see_the_decrypted_device_lock(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');
        $otherTechnician = StaffRecord::factory()->create();
        $shop = ShopRecord::factory()->create();
        $customer = CustomerRecord::factory()->create();

        $job = RepairJobRecord::factory()->create([
            'shop_id' => $shop->id,
            'customer_id' => $customer->id,
            'technician_staff_id' => $otherTechnician->id,
            'device_lock_type' => 'pattern',
            'device_lock_value' => '1-5-9',
        ]);

        $response = $this->actingAs($owner, 'staff')->get("/admin/repairs/{$job->id}");

        $response->assertInertia(fn ($page) => $page
            ->where('repair.device_lock_visible_to_you', true)
            ->where('repair.device_lock_value', '1-5-9'));
    }

    public function test_a_different_technician_cannot_see_the_decrypted_device_lock(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $assignedTechnician = StaffRecord::factory()->create();
        $viewingTechnician = StaffRecord::factory()->create();
        $viewingTechnician->assignRole('Technician');
        $shop = ShopRecord::factory()->create();
        $customer = CustomerRecord::factory()->create();

        $job = RepairJobRecord::factory()->create([
            'shop_id' => $shop->id,
            'customer_id' => $customer->id,
            'technician_staff_id' => $assignedTechnician->id,
            'device_lock_type' => 'code',
            'device_lock_value' => '7890',
        ]);

        $response = $this->actingAs($viewingTechnician, 'staff')->get("/admin/repairs/{$job->id}");

        $response->assertInertia(fn ($page) => $page
            ->where('repair.device_lock_visible_to_you', false)
            ->where('repair.device_lock_present', true)
            ->where('repair.device_lock_value', null));
    }

    public function test_the_raw_database_value_is_not_the_plaintext_code(): void
    {
        $job = RepairJobRecord::factory()->create([
            'device_lock_type' => 'code',
            'device_lock_value' => '7890',
        ]);

        $raw = DB::table('repair_jobs')->where('id', $job->id)->value('device_lock_value');

        $this->assertNotSame('7890', $raw);
        $this->assertNotNull($raw);
    }
}
