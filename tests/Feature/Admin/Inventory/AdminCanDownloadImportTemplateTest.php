<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Inventory;

use Database\Seeders\RolePermissionSeeder;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCanDownloadImportTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_with_inventory_create_can_download_the_csv_template(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        $response = $this->actingAs($owner, 'staff')->get('/admin/inventory/import/template');

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        ob_start();
        $response->baseResponse->sendContent();
        $streamed = ob_get_clean();

        $this->assertStringContainsString(
            'brand,model,category,condition,cost_price_minor,markup_percent,quantity,imei,low_stock_threshold',
            $streamed,
        );
    }

    public function test_a_staff_member_without_inventory_create_cannot_download_the_template(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');

        $response = $this->actingAs($cashier, 'staff')->get('/admin/inventory/import/template');

        $response->assertForbidden();
    }
}
