<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use Database\Seeders\RolePermissionSeeder;
use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authorized_staff_member_sees_both_registered_and_walk_in_customers(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        CustomerRecord::factory()->create(['name' => 'Registered Rita']);
        CustomerRecord::factory()->walkIn()->create(['name' => 'Walk-in Wale']);

        $response = $this->actingAs($owner, 'staff')->get('/admin/customers');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Customers/Index')
            ->where('customers.total', 2));
    }

    public function test_the_registration_filter_narrows_to_walk_ins_only(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        CustomerRecord::factory()->create();
        CustomerRecord::factory()->walkIn()->create(['name' => 'Only This One']);

        $response = $this->actingAs($owner, 'staff')->get('/admin/customers?registration=walk_in');

        $response->assertInertia(fn ($page) => $page
            ->where('customers.total', 1)
            ->where('customers.data.0.name', 'Only This One')
            ->where('customers.data.0.is_registered', false));
    }

    public function test_a_staff_member_without_customers_permission_is_forbidden(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $marketing = StaffRecord::factory()->create();
        $marketing->assignRole('Marketing Staff');

        $this->actingAs($marketing, 'staff')->get('/admin/customers')->assertForbidden();
    }
}
