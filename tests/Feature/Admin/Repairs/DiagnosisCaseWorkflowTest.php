<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Repairs;

use Database\Seeders\RolePermissionSeeder;
use Domain\Repair\Infrastructure\Persistence\Eloquent\DeviceProblemTagRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\DeviceTypeRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\DiagnosisCaseFaultRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\DiagnosisCaseRecord;
use Domain\Repair\Infrastructure\Persistence\Eloquent\RepairJobRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DiagnosisCaseWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_diagnosis_case_can_create_a_linked_repair_with_separate_fixed_faults(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $shop = ShopRecord::factory()->create();
        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        $technician = StaffRecord::factory()->create();
        $technician->assignRole('Technician');
        $this->grantShopAccess($cashier, $shop->id);
        $this->grantShopAccess($technician, $shop->id);

        $deviceType = DeviceTypeRecord::query()->create(['label' => 'Phone', 'icon' => 'phone', 'sort_order' => 1]);
        $screenFault = DeviceProblemTagRecord::query()->create([
            'device_type_id' => $deviceType->id,
            'label' => 'Screen fault',
            'sort_order' => 1,
        ]);
        $batteryFault = DeviceProblemTagRecord::query()->create([
            'device_type_id' => $deviceType->id,
            'label' => 'Battery fault',
            'sort_order' => 2,
        ]);

        $this->actingAs($cashier, 'staff')->post('/admin/repairs/diagnosis-cases', [
            'shop_id' => $shop->id,
            'customer_id' => '',
            'device_make' => 'Apple',
            'device_model' => 'iPhone 15 Pro',
            'reported_issue' => 'Screen and battery issues',
            'device_imei_serial' => 'IMEI123456',
            'device_lock_type' => 'none',
            'diagnosis_technician_staff_id' => $technician->id,
        ])->assertRedirect();

        $case = DiagnosisCaseRecord::query()->firstOrFail();

        $this->actingAs($cashier, 'staff')->post("/admin/repairs/diagnosis-cases/{$case->id}/result", [
            'outcome' => 'repairable',
            'diagnosis_notes' => 'Screen replacement now, battery can wait.',
            'diagnosis_fee_minor' => 200000,
            'revealed_faults' => [
                ['device_problem_tag_id' => $screenFault->id, 'label' => 'Screen fault', 'notes' => 'Cracked OLED'],
                ['device_problem_tag_id' => $batteryFault->id, 'label' => 'Battery fault', 'notes' => 'Weak health'],
            ],
        ])->assertRedirect();

        $screenDiagnosisFault = DiagnosisCaseFaultRecord::query()
            ->where('diagnosis_case_id', $case->id)
            ->where('device_problem_tag_id', $screenFault->id)
            ->firstOrFail();

        $this->actingAs($cashier, 'staff')->post("/admin/repairs/diagnosis-cases/{$case->id}/repairs", [
            'fault_ids' => [$screenDiagnosisFault->id],
        ])->assertRedirect();

        $repair = RepairJobRecord::query()->firstOrFail();
        $case->refresh();
        $this->assertSame($case->id, $repair->diagnosis_case_id);
        $this->assertSame('called_up', $case->status);
        $this->assertSame('awaiting_authorization', $repair->repair_status);
        $this->assertDatabaseHas('repair_job_problem_tags', [
            'repair_job_id' => $repair->id,
            'device_problem_tag_id' => $screenFault->id,
        ]);

        $this->actingAs($cashier, 'staff')
            ->get('/admin/repairs/diagnosis-cases')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/DiagnosisCases/Index')
                ->where('cases.data.0.status', 'called_up')
                ->where('cases.data.0.open_repair_id', $repair->id)
            );

        $this->actingAs($cashier, 'staff')->post("/admin/repairs/diagnosis-cases/{$case->id}/repairs", [
            'fault_ids' => [$screenDiagnosisFault->id],
        ])->assertSessionHasErrors('status');

        $repair->update(['repair_status' => 'in_progress']);

        $this->actingAs($cashier, 'staff')->post("/admin/repairs/{$repair->id}/complete", [
            'resolution_notes' => 'Replaced screen only.',
            'fixed_faults' => [
                [
                    'diagnosis_case_fault_id' => $screenDiagnosisFault->id,
                    'device_problem_tag_id' => $screenFault->id,
                    'label' => 'Screen fault',
                    'notes' => 'OLED replaced',
                ],
                [
                    'diagnosis_case_fault_id' => null,
                    'device_problem_tag_id' => null,
                    'label' => 'Loose display connector',
                    'notes' => 'Discovered during repair',
                ],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('repair_fixed_faults', [
            'repair_job_id' => $repair->id,
            'diagnosis_case_fault_id' => $screenDiagnosisFault->id,
            'label_snapshot' => 'Screen fault',
        ]);
        $this->assertDatabaseMissing('repair_fixed_faults', [
            'repair_job_id' => $repair->id,
            'device_problem_tag_id' => $batteryFault->id,
        ]);
        $this->assertDatabaseHas('repair_fixed_faults', [
            'repair_job_id' => $repair->id,
            'diagnosis_case_fault_id' => null,
            'label_snapshot' => 'Loose display connector',
        ]);

        $repair->update(['repair_status' => 'completed']);

        $this->actingAs($cashier, 'staff')->post("/admin/repairs/diagnosis-cases/{$case->id}/repairs", [
            'fault_ids' => [$screenDiagnosisFault->id],
        ])->assertRedirect();

        $this->assertSame(2, RepairJobRecord::query()->where('diagnosis_case_id', $case->id)->count());
        $this->assertSame('awaiting_authorization', RepairJobRecord::query()->where('diagnosis_case_id', $case->id)->latest('id')->value('repair_status'));
    }

    public function test_repair_created_from_diagnosis_case_inherits_technician_and_fee_and_earns_fault_fix_commission(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $shop = ShopRecord::factory()->create();
        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        $technician = StaffRecord::factory()->create();
        $technician->assignRole('Technician');
        $this->grantShopAccess($cashier, $shop->id);
        $this->grantShopAccess($technician, $shop->id);

        $deviceType = DeviceTypeRecord::query()->create(['label' => 'Phone', 'icon' => 'phone', 'sort_order' => 1]);
        $screenFault = DeviceProblemTagRecord::query()->create([
            'device_type_id' => $deviceType->id,
            'label' => 'Screen fault',
            'sort_order' => 1,
            'commission_amount_minor' => 150000,
        ]);

        $this->actingAs($cashier, 'staff')->post('/admin/repairs/diagnosis-cases', [
            'shop_id' => $shop->id,
            'customer_id' => '',
            'device_make' => 'Apple',
            'device_model' => 'iPhone 15 Pro',
            'reported_issue' => 'Screen issue',
            'device_lock_type' => 'none',
            'diagnosis_technician_staff_id' => $technician->id,
        ])->assertRedirect();

        $case = DiagnosisCaseRecord::query()->firstOrFail();

        $this->actingAs($cashier, 'staff')->post("/admin/repairs/diagnosis-cases/{$case->id}/result", [
            'outcome' => 'repairable',
            'diagnosis_fee_minor' => 200000,
            'revealed_faults' => [
                ['device_problem_tag_id' => $screenFault->id, 'label' => 'Screen fault', 'notes' => 'Cracked OLED'],
            ],
        ])->assertRedirect();

        $screenDiagnosisFault = DiagnosisCaseFaultRecord::query()->where('diagnosis_case_id', $case->id)->firstOrFail();

        $this->actingAs($cashier, 'staff')->post("/admin/repairs/diagnosis-cases/{$case->id}/repairs", [
            'fault_ids' => [$screenDiagnosisFault->id],
        ])->assertRedirect();

        $repair = RepairJobRecord::query()->firstOrFail();

        // The repair starts with the case's technician/fee already in place —
        // an editable starting value, not a forced lock to those values.
        $this->assertSame($technician->id, $repair->technician_staff_id);
        $this->assertDatabaseHas('repair_diagnosis_fees', [
            'repair_job_id' => $repair->id,
            'amount_minor' => 200000,
        ]);

        $repair->update(['repair_status' => 'in_progress']);

        $this->actingAs($cashier, 'staff')->post("/admin/repairs/{$repair->id}/complete", [
            'resolution_notes' => 'Replaced screen.',
            'fixed_faults' => [
                [
                    'diagnosis_case_fault_id' => $screenDiagnosisFault->id,
                    'device_problem_tag_id' => $screenFault->id,
                    'label' => 'Screen fault',
                    'notes' => 'OLED replaced',
                ],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('commission_ledger_entries', [
            'staff_id' => $technician->id,
            'source_type' => 'repair',
            'source_id' => $repair->id,
            'commissionable_amount_minor' => 150000,
            'entry_type' => 'earned',
            'status' => 'earned',
        ]);
    }

    public function test_fixed_fault_without_a_commissionable_tag_earns_no_commission(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $shop = ShopRecord::factory()->create();
        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        $technician = StaffRecord::factory()->create();
        $technician->assignRole('Technician');
        $this->grantShopAccess($cashier, $shop->id);
        $this->grantShopAccess($technician, $shop->id);

        $deviceType = DeviceTypeRecord::query()->create(['label' => 'Phone', 'icon' => 'phone', 'sort_order' => 1]);
        $batteryFault = DeviceProblemTagRecord::query()->create([
            'device_type_id' => $deviceType->id,
            'label' => 'Battery fault',
            'sort_order' => 1,
            'commission_amount_minor' => null,
        ]);

        $this->actingAs($cashier, 'staff')->post('/admin/repairs/diagnosis-cases', [
            'shop_id' => $shop->id,
            'customer_id' => '',
            'device_make' => 'Apple',
            'device_model' => 'iPhone 15 Pro',
            'reported_issue' => 'Battery issue',
            'device_lock_type' => 'none',
            'diagnosis_technician_staff_id' => $technician->id,
        ])->assertRedirect();

        $case = DiagnosisCaseRecord::query()->firstOrFail();

        $this->actingAs($cashier, 'staff')->post("/admin/repairs/diagnosis-cases/{$case->id}/result", [
            'outcome' => 'repairable',
            'revealed_faults' => [
                ['device_problem_tag_id' => $batteryFault->id, 'label' => 'Battery fault', 'notes' => ''],
            ],
        ])->assertRedirect();

        $diagnosisFault = DiagnosisCaseFaultRecord::query()->where('diagnosis_case_id', $case->id)->firstOrFail();

        $this->actingAs($cashier, 'staff')->post("/admin/repairs/diagnosis-cases/{$case->id}/repairs", [
            'fault_ids' => [$diagnosisFault->id],
        ])->assertRedirect();

        $repair = RepairJobRecord::query()->firstOrFail();
        $repair->update(['repair_status' => 'in_progress']);

        $this->actingAs($cashier, 'staff')->post("/admin/repairs/{$repair->id}/complete", [
            'resolution_notes' => 'Replaced battery.',
            'fixed_faults' => [
                [
                    'diagnosis_case_fault_id' => $diagnosisFault->id,
                    'device_problem_tag_id' => $batteryFault->id,
                    'label' => 'Battery fault',
                    'notes' => '',
                ],
                [
                    'diagnosis_case_fault_id' => null,
                    'device_problem_tag_id' => null,
                    'label' => 'Free-text discovered issue',
                    'notes' => '',
                ],
            ],
        ])->assertRedirect();

        $this->assertDatabaseCount('commission_ledger_entries', 0);
    }

    public function test_assigning_a_diagnosis_technician_writes_an_audit_entry(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $shop = ShopRecord::factory()->create();
        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        $technician = StaffRecord::factory()->create();
        $technician->assignRole('Technician');
        $this->grantShopAccess($cashier, $shop->id);

        $this->actingAs($cashier, 'staff')->post('/admin/repairs/diagnosis-cases', [
            'shop_id' => $shop->id,
            'customer_id' => '',
            'device_make' => 'Apple',
            'device_model' => 'iPhone 15 Pro',
            'device_lock_type' => 'none',
        ])->assertRedirect();

        $case = DiagnosisCaseRecord::query()->firstOrFail();

        $this->actingAs($cashier, 'staff')->post("/admin/repairs/diagnosis-cases/{$case->id}/assign-technician", [
            'diagnosis_technician_staff_id' => $technician->id,
        ])->assertRedirect();

        $case->refresh();
        $this->assertSame($technician->id, $case->diagnosis_technician_staff_id);
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'Repair',
            'event_type' => 'DiagnosisTechnicianAssigned',
            'subject_type' => 'diagnosis_case',
            'subject_id' => $case->id,
        ]);
    }

    public function test_diagnosis_technician_cannot_be_reassigned_once_the_case_is_called_up(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $shop = ShopRecord::factory()->create();
        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        $technician = StaffRecord::factory()->create();
        $technician->assignRole('Technician');
        $otherTechnician = StaffRecord::factory()->create();
        $otherTechnician->assignRole('Technician');
        $this->grantShopAccess($cashier, $shop->id);

        $this->actingAs($cashier, 'staff')->post('/admin/repairs/diagnosis-cases', [
            'shop_id' => $shop->id,
            'customer_id' => '',
            'device_make' => 'Apple',
            'device_model' => 'iPhone 15 Pro',
            'device_lock_type' => 'none',
            'diagnosis_technician_staff_id' => $technician->id,
        ])->assertRedirect();

        $case = DiagnosisCaseRecord::query()->firstOrFail();
        $case->update(['status' => 'called_up']);

        $this->actingAs($cashier, 'staff')->post("/admin/repairs/diagnosis-cases/{$case->id}/assign-technician", [
            'diagnosis_technician_staff_id' => $otherTechnician->id,
        ])->assertSessionHasErrors('diagnosis_technician_staff_id');

        $case->refresh();
        $this->assertSame($technician->id, $case->diagnosis_technician_staff_id);
    }
}
