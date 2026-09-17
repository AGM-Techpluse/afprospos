<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Warranty;

use Database\Seeders\RolePermissionSeeder;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Domain\Warranty\Infrastructure\Persistence\Eloquent\WarrantyClaimRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCanAssessWarrantyClaimTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_technician_can_assess_a_submitted_claim_as_eligible(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $technician = StaffRecord::factory()->create();
        $technician->assignRole('Technician');

        $claim = WarrantyClaimRecord::factory()->create(['resolution_state' => 'submitted']);

        $response = $this->actingAs($technician, 'staff')
            ->post("/admin/warranty/claims/{$claim->id}/assess", ['eligible' => '1', 'notes' => 'Confirmed manufacturing defect.']);

        $response->assertRedirect();
        $this->assertDatabaseHas('warranty_claims', [
            'id' => $claim->id,
            'resolution_state' => 'eligible',
            'assessment_notes' => 'Confirmed manufacturing defect.',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'Warranty',
            'event_type' => 'WarrantyClaimAssessed',
            'subject_type' => 'warranty_claim',
            'subject_id' => $claim->id,
        ]);
    }

    public function test_a_cashier_without_warranty_assess_cannot_assess_a_claim(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier'); // has warranty.view/create only, not warranty.assess

        $claim = WarrantyClaimRecord::factory()->create(['resolution_state' => 'submitted']);

        $response = $this->actingAs($cashier, 'staff')
            ->post("/admin/warranty/claims/{$claim->id}/assess", ['eligible' => '1']);

        $response->assertForbidden();
        $this->assertDatabaseHas('warranty_claims', ['id' => $claim->id, 'resolution_state' => 'submitted']);
    }
}
