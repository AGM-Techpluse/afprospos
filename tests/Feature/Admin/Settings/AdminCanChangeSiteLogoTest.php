<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Settings;

use Database\Seeders\RolePermissionSeeder;
use Domain\Shared\Application\Queries\SiteLogoQuery;
use Domain\Shared\Infrastructure\Persistence\Eloquent\SiteSettingRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Deliberately writes to a real file under public/uploads/site (not the
 * storage:link symlink) — several shared-hosting Apache configs disable
 * FollowSymLinks, which 403s anything reached through that symlink even
 * with correct file permissions (see SiteAppearanceController::updateLogo()'s
 * docblock) — so these assertions check the real filesystem path, with
 * tearDown cleaning up whatever the test run created.
 */
class AdminCanChangeSiteLogoTest extends TestCase
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

    protected function tearDown(): void
    {
        File::deleteDirectory(public_path('uploads/site'));

        parent::tearDown();
    }

    public function test_an_uploaded_logo_is_stored_and_served_to_every_page(): void
    {
        $this->actingAs($this->owner, 'staff')
            ->post('/admin/settings/appearance/logo', ['logo' => UploadedFile::fake()->image('logo.png', 64, 64)])
            ->assertRedirect();

        $setting = SiteSettingRecord::query()->find(SiteLogoQuery::KEY);
        $this->assertNotNull($setting);
        $this->assertTrue(File::exists(public_path($setting->value['path'])));

        $this->get('/admin/dashboard')->assertInertia(fn ($page) => $page
            ->where('app_logo', asset($setting->value['path'])));
    }

    public function test_uploading_a_new_logo_removes_the_previous_file(): void
    {
        $this->actingAs($this->owner, 'staff')
            ->post('/admin/settings/appearance/logo', ['logo' => UploadedFile::fake()->image('first.png', 64, 64)]);

        $firstPath = SiteSettingRecord::query()->find(SiteLogoQuery::KEY)->value['path'];
        $this->assertTrue(File::exists(public_path($firstPath)));

        $this->actingAs($this->owner, 'staff')
            ->post('/admin/settings/appearance/logo', ['logo' => UploadedFile::fake()->image('second.png', 64, 64)]);

        $secondPath = SiteSettingRecord::query()->find(SiteLogoQuery::KEY)->value['path'];

        $this->assertNotSame($firstPath, $secondPath);
        $this->assertFalse(File::exists(public_path($firstPath)));
        $this->assertTrue(File::exists(public_path($secondPath)));
    }

    public function test_a_non_image_file_is_rejected(): void
    {
        $this->actingAs($this->owner, 'staff')
            ->from('/admin/settings/appearance')
            ->post('/admin/settings/appearance/logo', ['logo' => UploadedFile::fake()->create('logo.pdf', 100)])
            ->assertSessionHasErrors('logo');

        $this->assertDatabaseMissing('site_settings', ['key' => SiteLogoQuery::KEY]);
    }

    public function test_reset_removes_the_stored_file_and_setting(): void
    {
        $this->actingAs($this->owner, 'staff')
            ->post('/admin/settings/appearance/logo', ['logo' => UploadedFile::fake()->image('logo.png', 64, 64)]);

        $path = SiteSettingRecord::query()->find(SiteLogoQuery::KEY)->value['path'];

        $this->actingAs($this->owner, 'staff')->delete('/admin/settings/appearance/logo')->assertRedirect();

        $this->assertDatabaseMissing('site_settings', ['key' => SiteLogoQuery::KEY]);
        $this->assertFalse(File::exists(public_path($path)));
    }

    public function test_a_cashier_cannot_change_the_site_logo(): void
    {
        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');

        $this->actingAs($cashier, 'staff')
            ->post('/admin/settings/appearance/logo', ['logo' => UploadedFile::fake()->image('logo.png', 64, 64)])
            ->assertForbidden();

        $this->assertDatabaseMissing('site_settings', ['key' => SiteLogoQuery::KEY]);
    }
}
