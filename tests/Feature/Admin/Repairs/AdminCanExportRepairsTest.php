<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Repairs;

use Database\Seeders\RolePermissionSeeder;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCanExportRepairsTest extends TestCase
{
    use RefreshDatabase;

    public function test_exporting_by_filter_only_includes_matching_rows(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        $completed = RepairJobRecord::factory()->create(['repair_status' => 'completed']);
        RepairJobRecord::factory()->create(['repair_status' => 'in_progress']);

        $response = $this->actingAs($owner, 'staff')->get('/admin/repairs/export?status=completed&shop_id=');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();
        $rows = array_values(array_filter(explode("\n", trim($csv))));

        $this->assertCount(2, $rows);
        $this->assertStringContainsString((string) $completed->id, $rows[1]);
    }

    public function test_exporting_selected_ids_ignores_the_filter(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        $selected = RepairJobRecord::factory()->create(['repair_status' => 'received']);
        RepairJobRecord::factory()->create(['repair_status' => 'received']);

        $response = $this->actingAs($owner, 'staff')->get("/admin/repairs/export?ids[]={$selected->id}");

        $response->assertOk();
        $csv = $response->streamedContent();
        $rows = array_values(array_filter(explode("\n", trim($csv))));

        $this->assertCount(2, $rows);
        $this->assertStringContainsString((string) $selected->id, $rows[1]);
    }
}
