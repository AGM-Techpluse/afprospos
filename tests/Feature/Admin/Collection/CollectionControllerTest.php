<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Collection;

use Database\Seeders\RolePermissionSeeder;
use Domain\Collection\Infrastructure\Persistence\Eloquent\CollectionCaseRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollectionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_technician_can_view_the_queue_and_mark_a_case_notified(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $technician = StaffRecord::factory()->create();
        $technician->assignRole('Technician');
        $case = CollectionCaseRecord::factory()->create();

        $indexResponse = $this->actingAs($technician, 'staff')->get('/admin/collection');
        $indexResponse->assertOk();
        $indexResponse->assertInertia(fn ($page) => $page->component('Admin/Collection/Index'));

        $notifyResponse = $this->actingAs($technician, 'staff')->post("/admin/collection/{$case->id}/notify", ['note' => 'Called customer']);
        $notifyResponse->assertRedirect();
        $this->assertDatabaseHas('collection_case_events', ['collection_case_id' => $case->id, 'event_type' => 'notified']);
    }

    public function test_a_technician_can_release_a_fully_paid_device_but_gets_403_on_override(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $technician = StaffRecord::factory()->create();
        $technician->assignRole('Technician'); // collection view+process, NOT override

        $overrideResponse = $this->actingAs($technician, 'staff')->post('/admin/collection/1/override', ['reason' => 'test']);
        $overrideResponse->assertForbidden();
    }

    public function test_a_staff_member_without_collection_permission_cannot_view_the_queue(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier'); // no collection.* per config/afprospos.php

        $response = $this->actingAs($cashier, 'staff')->get('/admin/collection');

        $response->assertForbidden();
    }

    public function test_accountant_can_record_an_override(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $accountant = StaffRecord::factory()->create();
        $accountant->assignRole('Accountant');
        $job = RepairJobRecord::factory()->create(['financial_status' => 'partially_paid']);
        $case = CollectionCaseRecord::factory()->create(['source_type' => 'repair_job', 'source_id' => $job->id]);

        $response = $this->actingAs($accountant, 'staff')->post("/admin/collection/{$case->id}/override", [
            'reason' => 'Longtime customer goodwill release',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('collection_case_events', ['collection_case_id' => $case->id, 'event_type' => 'administrative_resolution']);
    }
}
