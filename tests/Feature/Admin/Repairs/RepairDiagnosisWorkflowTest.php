<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Repairs;

use Database\Seeders\RolePermissionSeeder;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** REP-BR-29: component testing (this file's "diagnosis" endpoint) and the actual diagnosis (root cause + outcome, the "diagnosis-conclusion" endpoint) are distinct steps. */
class RepairDiagnosisWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_recording_a_component_observation_does_not_move_the_job_status(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $manager = StaffRecord::factory()->create();
        $manager->assignRole('Manager');
        $shop = ShopRecord::factory()->create();
        $this->grantShopAccess($manager, $shop->id);
        $job = RepairJobRecord::factory()->create(['shop_id' => $shop->id, 'repair_status' => 'received']);

        $response = $this->actingAs($manager, 'staff')->post("/admin/repairs/{$job->id}/diagnosis", [
            'component' => 'screen',
            'condition' => 'faulty',
            'notes' => 'Cracked glass',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('repair_jobs', ['id' => $job->id, 'repair_status' => 'diagnosing']);
        $this->assertDatabaseHas('repair_diagnoses', ['repair_job_id' => $job->id, 'component' => 'screen', 'condition' => 'faulty']);
    }

    public function test_a_saved_repairable_conclusion_is_sent_to_quote_and_authorization_by_staff(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $manager = StaffRecord::factory()->create();
        $manager->assignRole('Manager');
        $shop = ShopRecord::factory()->create();
        $this->grantShopAccess($manager, $shop->id);
        $job = RepairJobRecord::factory()->create(['shop_id' => $shop->id, 'repair_status' => 'diagnosing']);

        $response = $this->actingAs($manager, 'staff')->post("/admin/repairs/{$job->id}/diagnosis-conclusion", [
            'root_cause' => 'Cracked screen assembly',
            'outcome' => 'repairable',
        ]);

        $response->assertRedirect();
        // Saving the outcome does not move the job on; staff send it to quote as a separate step.
        $this->assertDatabaseHas('repair_jobs', ['id' => $job->id, 'repair_status' => 'diagnosing']);

        $this->actingAs($manager, 'staff')->post("/admin/repairs/{$job->id}/send-to-quote")->assertRedirect();
        $this->assertDatabaseHas('repair_jobs', ['id' => $job->id, 'repair_status' => 'awaiting_authorization']);
        $this->assertDatabaseHas('repair_diagnosis_conclusions', [
            'repair_job_id' => $job->id,
            'root_cause' => 'Cracked screen assembly',
            'outcome' => 'repairable',
        ]);
    }

    public function test_a_component_observation_requires_component_and_condition(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $manager = StaffRecord::factory()->create();
        $manager->assignRole('Manager');
        $shop = ShopRecord::factory()->create();
        $this->grantShopAccess($manager, $shop->id);
        $job = RepairJobRecord::factory()->create(['shop_id' => $shop->id, 'repair_status' => 'diagnosing']);

        $response = $this->actingAs($manager, 'staff')->post("/admin/repairs/{$job->id}/diagnosis", [
            'component' => '',
            'condition' => '',
        ]);

        $response->assertSessionHasErrors(['component', 'condition']);
        $this->assertDatabaseCount('repair_diagnoses', 0);
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

    public function test_a_staff_member_without_repairs_edit_cannot_record_a_diagnosis_conclusion(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $productStaff = StaffRecord::factory()->create();
        $productStaff->assignRole('Product Staff'); // repairs.view only, no edit
        $job = RepairJobRecord::factory()->create(['repair_status' => 'diagnosing']);

        $response = $this->actingAs($productStaff, 'staff')->post("/admin/repairs/{$job->id}/diagnosis-conclusion", [
            'root_cause' => 'Cracked screen assembly',
            'outcome' => 'repairable',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('repair_jobs', ['id' => $job->id, 'repair_status' => 'diagnosing']);
    }
}
