<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Repairs;

use Database\Seeders\RolePermissionSeeder;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepairDiagnosisWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_recording_a_diagnosis_with_outcome_moves_the_job_to_awaiting_authorization(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $technician = StaffRecord::factory()->create();
        $technician->assignRole('Technician');
        $job = RepairJobRecord::factory()->create(['repair_status' => 'received']);

        $response = $this->actingAs($technician, 'staff')->post("/admin/repairs/{$job->id}/diagnosis", [
            'component' => 'screen',
            'condition' => 'faulty',
            'notes' => 'Cracked glass',
            'outcome' => 'repairable',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('repair_jobs', ['id' => $job->id, 'repair_status' => 'awaiting_authorization']);
        $this->assertDatabaseHas('repair_diagnoses', ['repair_job_id' => $job->id, 'component' => 'screen', 'outcome' => 'repairable']);
    }

    public function test_a_staff_member_without_repairs_edit_cannot_record_a_diagnosis(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $productStaff = StaffRecord::factory()->create();
        $productStaff->assignRole('Product Staff'); // repairs.view only, no edit
        $job = RepairJobRecord::factory()->create(['repair_status' => 'received']);

        $response = $this->actingAs($productStaff, 'staff')->post("/admin/repairs/{$job->id}/diagnosis", [
            'component' => 'screen',
            'condition' => 'faulty',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('repair_jobs', ['id' => $job->id, 'repair_status' => 'received']);
    }
}
