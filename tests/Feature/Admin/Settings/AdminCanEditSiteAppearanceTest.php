<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Settings;

use Database\Seeders\RolePermissionSeeder;
use Domain\Shared\Application\Queries\WelcomePaletteQuery;
use Domain\Shared\Infrastructure\Persistence\Eloquent\SiteSettingRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCanEditSiteAppearanceTest extends TestCase
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

    public function test_saved_colours_are_served_to_the_landing_page(): void
    {
        $this->actingAs($this->owner, 'staff')
            ->put('/admin/settings/appearance', ['palette' => ['accent' => '#112233']])
            ->assertRedirect();

        $this->get('/')->assertInertia(fn ($page) => $page
            ->component('Welcome')
            ->where('palette.accent', '#112233')
            ->where('palette.text', WelcomePaletteQuery::defaults()['text']));
    }

    public function test_an_invalid_hex_colour_is_rejected_and_nothing_is_saved(): void
    {
        $this->actingAs($this->owner, 'staff')
            ->from('/admin/settings/appearance')
            ->put('/admin/settings/appearance', ['palette' => ['accent' => 'blue']])
            ->assertSessionHasErrors('palette.accent');

        $this->assertDatabaseMissing('site_settings', ['key' => WelcomePaletteQuery::KEY]);
    }

    public function test_an_unknown_colour_token_is_rejected(): void
    {
        $this->actingAs($this->owner, 'staff')
            ->from('/admin/settings/appearance')
            ->put('/admin/settings/appearance', ['palette' => ['made_up' => '#112233']])
            ->assertSessionHasErrors('palette');

        $this->assertDatabaseMissing('site_settings', ['key' => WelcomePaletteQuery::KEY]);
    }

    public function test_reset_returns_the_landing_page_to_its_original_colours(): void
    {
        SiteSettingRecord::query()->create([
            'key' => WelcomePaletteQuery::KEY,
            'value' => ['accent' => '#112233'],
            'updated_by_staff_id' => $this->owner->id,
        ]);

        $this->actingAs($this->owner, 'staff')->delete('/admin/settings/appearance')->assertRedirect();

        $this->assertDatabaseMissing('site_settings', ['key' => WelcomePaletteQuery::KEY]);
        $this->assertSame(WelcomePaletteQuery::defaults()['accent'], app(WelcomePaletteQuery::class)->resolved()['accent']);
    }

    public function test_a_cashier_cannot_change_the_site_appearance_and_nothing_is_saved(): void
    {
        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');

        $this->actingAs($cashier, 'staff')
            ->put('/admin/settings/appearance', ['palette' => ['accent' => '#112233']])
            ->assertForbidden();

        $this->assertDatabaseMissing('site_settings', ['key' => WelcomePaletteQuery::KEY]);
    }
}
