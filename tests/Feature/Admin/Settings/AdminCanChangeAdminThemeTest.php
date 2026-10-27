<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Settings;

use Database\Seeders\RolePermissionSeeder;
use Domain\Shared\Application\Queries\AdminThemeQuery;
use Domain\Shared\Infrastructure\Persistence\Eloquent\SiteSettingRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCanChangeAdminThemeTest extends TestCase
{
    use RefreshDatabase;

    private StaffRecord $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->owner = StaffRecord::factory()->create();
        $this->owner->assignRole('Shop Owner');
    }

    public function test_a_saved_accent_colour_is_shared_on_every_admin_page(): void
    {
        $this->actingAs($this->owner, 'staff')
            ->put('/admin/settings/appearance/admin-theme', ['accent' => '#F97316'])
            ->assertRedirect();

        $this->actingAs($this->owner, 'staff')
            ->get('/admin/dashboard')
            ->assertInertia(fn ($page) => $page->where('admin_theme_accent', '#F97316'));
    }

    public function test_an_invalid_hex_colour_is_rejected_and_nothing_is_saved(): void
    {
        $this->actingAs($this->owner, 'staff')
            ->from('/admin/settings/appearance')
            ->put('/admin/settings/appearance/admin-theme', ['accent' => 'orange'])
            ->assertSessionHasErrors('accent');

        $this->assertDatabaseMissing('site_settings', ['key' => AdminThemeQuery::KEY]);
    }

    public function test_reset_returns_to_the_default_accent(): void
    {
        SiteSettingRecord::query()->create([
            'key' => AdminThemeQuery::KEY,
            'value' => ['accent' => '#F97316'],
            'updated_by_staff_id' => $this->owner->id,
        ]);

        $this->actingAs($this->owner, 'staff')->delete('/admin/settings/appearance/admin-theme')->assertRedirect();

        $this->assertDatabaseMissing('site_settings', ['key' => AdminThemeQuery::KEY]);
        $this->assertSame(AdminThemeQuery::defaults(), app(AdminThemeQuery::class)->resolved());
    }

    public function test_a_cashier_cannot_change_the_admin_theme_and_nothing_is_saved(): void
    {
        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');

        $this->actingAs($cashier, 'staff')
            ->put('/admin/settings/appearance/admin-theme', ['accent' => '#F97316'])
            ->assertForbidden();

        $this->assertDatabaseMissing('site_settings', ['key' => AdminThemeQuery::KEY]);
    }
}
