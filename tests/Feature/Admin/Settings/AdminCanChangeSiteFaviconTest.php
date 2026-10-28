<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Settings;

use Database\Seeders\RolePermissionSeeder;
use Domain\Shared\Application\Queries\SiteFaviconQuery;
use Domain\Shared\Infrastructure\Persistence\Eloquent\SiteSettingRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Same symlink-bypass reasoning as AdminCanChangeSiteLogoTest — writes to a
 * real file under public/uploads/site, not the storage:link symlink.
 */
class AdminCanChangeSiteFaviconTest extends TestCase
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

    public function test_an_uploaded_favicon_is_stored_and_served_in_the_page_head(): void
    {
        $this->actingAs($this->owner, 'staff')
            ->post('/admin/settings/appearance/favicon', ['favicon' => UploadedFile::fake()->image('favicon.png', 32, 32)])
            ->assertRedirect();

        $setting = SiteSettingRecord::query()->find(SiteFaviconQuery::KEY);
        $this->assertNotNull($setting);
        $this->assertTrue(File::exists(public_path($setting->value['path'])));

        $this->get('/admin/dashboard')->assertSee(
            '<link rel="icon" href="'.asset($setting->value['path']).'">',
            false,
        );
    }

    public function test_uploading_a_new_favicon_removes_the_previous_file(): void
    {
        $this->actingAs($this->owner, 'staff')
            ->post('/admin/settings/appearance/favicon', ['favicon' => UploadedFile::fake()->image('first.png', 32, 32)]);

        $firstPath = SiteSettingRecord::query()->find(SiteFaviconQuery::KEY)->value['path'];
        $this->assertTrue(File::exists(public_path($firstPath)));

        $this->actingAs($this->owner, 'staff')
            ->post('/admin/settings/appearance/favicon', ['favicon' => UploadedFile::fake()->image('second.png', 32, 32)]);

        $secondPath = SiteSettingRecord::query()->find(SiteFaviconQuery::KEY)->value['path'];

        $this->assertNotSame($firstPath, $secondPath);
        $this->assertFalse(File::exists(public_path($firstPath)));
        $this->assertTrue(File::exists(public_path($secondPath)));
    }

    public function test_a_non_image_file_is_rejected(): void
    {
        $this->actingAs($this->owner, 'staff')
            ->from('/admin/settings/appearance')
            ->post('/admin/settings/appearance/favicon', ['favicon' => UploadedFile::fake()->create('favicon.pdf', 100)])
            ->assertSessionHasErrors('favicon');

        $this->assertDatabaseMissing('site_settings', ['key' => SiteFaviconQuery::KEY]);
    }

    public function test_reset_removes_the_stored_file_and_setting(): void
    {
        $this->actingAs($this->owner, 'staff')
            ->post('/admin/settings/appearance/favicon', ['favicon' => UploadedFile::fake()->image('favicon.png', 32, 32)]);

        $path = SiteSettingRecord::query()->find(SiteFaviconQuery::KEY)->value['path'];

        $this->actingAs($this->owner, 'staff')->delete('/admin/settings/appearance/favicon')->assertRedirect();

        $this->assertDatabaseMissing('site_settings', ['key' => SiteFaviconQuery::KEY]);
        $this->assertFalse(File::exists(public_path($path)));
    }

    public function test_a_cashier_cannot_change_the_favicon(): void
    {
        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');

        $this->actingAs($cashier, 'staff')
            ->post('/admin/settings/appearance/favicon', ['favicon' => UploadedFile::fake()->image('favicon.png', 32, 32)])
            ->assertForbidden();

        $this->assertDatabaseMissing('site_settings', ['key' => SiteFaviconQuery::KEY]);
    }
}
