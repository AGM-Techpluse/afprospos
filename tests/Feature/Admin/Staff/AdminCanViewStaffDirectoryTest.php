<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Staff;

use Database\Seeders\RolePermissionSeeder;
use Domain\RBAC\Infrastructure\Persistence\Eloquent\StaffShopGrantRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression coverage: StaffDirectoryQuery::all()/find() previously used a
 * `static fn` closure that called `$this->toArray()`, which is a fatal
 * error at runtime ("Using $this when not in object context") but was
 * never caught because no test exercised the actual index/show HTTP
 * routes — only the write-side actions were tested directly.
 */
class AdminCanViewStaffDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_lets_an_admin_view_the_staff_directory(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        StaffRecord::factory()->create();

        $response = $this->actingAs($owner, 'staff')->get('/admin/staff');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Admin/Staff/Index'));
    }

    public function test_lets_an_admin_view_a_single_staff_member(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        $target = StaffRecord::factory()->create();

        $response = $this->actingAs($owner, 'staff')->get("/admin/staff/{$target->id}");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Admin/Staff/Show'));
    }

    public function test_search_narrows_the_staff_directory_by_name_or_email(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        StaffRecord::factory()->create(['name' => 'Amaka Okafor']);
        StaffRecord::factory()->create(['name' => 'Bello Tunde']);

        $response = $this->actingAs($owner, 'staff')->get('/admin/staff?search=Amaka');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('staff.total', 1)
            ->where('staff.data.0.name', 'Amaka Okafor'));
    }

    public function test_status_filter_narrows_the_staff_directory(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        StaffRecord::factory()->create(['status' => 'active']);
        StaffRecord::factory()->deactivated()->create();

        $response = $this->actingAs($owner, 'staff')->get('/admin/staff?status=deactivated');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->where('staff.total', 1));
    }

    public function test_role_filter_narrows_the_staff_directory(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');

        StaffRecord::factory()->create()->assignRole('Technician');

        $response = $this->actingAs($owner, 'staff')->get('/admin/staff?role=Cashier');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('staff.total', 1)
            ->where('staff.data.0.id', $cashier->id));
    }

    public function test_staff_directory_paginates_results(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        StaffRecord::factory()->count(25)->create();

        $response = $this->actingAs($owner, 'staff')->get('/admin/staff');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('staff.per_page', 20)
            ->where('staff.current_page', 1)
            ->where('staff.last_page', 2)
            ->where('staff.total', 26)
            ->has('staff.data', 20));
    }

    public function test_shop_id_filter_narrows_the_directory_to_that_shops_granted_staff_plus_owners(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        $shopA = ShopRecord::factory()->create();
        $shopB = ShopRecord::factory()->create();

        $cashierA = StaffRecord::factory()->create();
        $cashierA->assignRole('Cashier');
        StaffShopGrantRecord::query()->create([
            'staff_id' => $cashierA->id,
            'shop_id' => $shopA->id,
            'granted_at' => now(),
        ]);

        $cashierB = StaffRecord::factory()->create();
        $cashierB->assignRole('Cashier');
        StaffShopGrantRecord::query()->create([
            'staff_id' => $cashierB->id,
            'shop_id' => $shopB->id,
            'granted_at' => now(),
        ]);

        $response = $this->actingAs($owner, 'staff')->get("/admin/staff?shop_id={$shopA->id}");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('filters.shop_id', $shopA->id)
            // Exactly the owner (implicit all-shop access) plus the cashier explicitly granted to shop A —
            // never the cashier granted only to shop B.
            ->where('staff.total', 2));
    }
}
