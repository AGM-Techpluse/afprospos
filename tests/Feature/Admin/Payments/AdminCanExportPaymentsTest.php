<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Payments;

use Database\Seeders\RolePermissionSeeder;
use Domain\Payments\Infrastructure\Persistence\Eloquent\PaymentTransactionRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCanExportPaymentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_exporting_by_filter_only_includes_matching_rows(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        $confirmed = PaymentTransactionRecord::factory()->create(['status' => 'confirmed']);
        PaymentTransactionRecord::factory()->create(['status' => 'disputed']);

        $response = $this->actingAs($owner, 'staff')->get('/admin/payments/export?status=confirmed');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();
        $this->assertStringContainsString((string) $confirmed->id, $csv);
        $this->assertStringContainsString('confirmed', $csv);
        $this->assertStringNotContainsString('disputed', $csv);
    }

    public function test_exporting_selected_ids_ignores_the_filter_and_exports_exactly_those_rows(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $owner = StaffRecord::factory()->create();
        $owner->assignRole('Shop Owner');

        $selected = PaymentTransactionRecord::factory()->create(['status' => 'disputed']);
        $notSelected = PaymentTransactionRecord::factory()->create(['status' => 'disputed']);

        $response = $this->actingAs($owner, 'staff')->get("/admin/payments/export?ids[]={$selected->id}");

        $response->assertOk();
        $csv = $response->streamedContent();
        $rows = array_values(array_filter(explode("\n", trim($csv))));

        $this->assertCount(2, $rows); // header + exactly one data row
        $this->assertStringContainsString((string) $selected->id, $rows[1]);
    }
}
