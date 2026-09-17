<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Dashboard;

use Database\Seeders\RolePermissionSeeder;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardHidesLoginActivityWithoutAuditPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_shop_owner_sees_login_activity(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        $this->actingAs($owner, 'staff')
            ->get('/admin/dashboard')
            ->assertInertia(fn ($page) => $page->where('loginActivity', fn ($value) => $value !== null));
    }

    public function test_a_cashier_does_not_see_login_activity(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier'); // has no audit.* permissions per config/afprospos.php

        $this->actingAs($cashier, 'staff')
            ->get('/admin/dashboard')
            ->assertInertia(fn ($page) => $page->where('loginActivity', null));
    }
}
