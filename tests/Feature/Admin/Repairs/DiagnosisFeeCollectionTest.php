<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Repairs;

use Database\Seeders\RolePermissionSeeder;
use Domain\Repair\Infrastructure\Persistence\Eloquent\DiagnosisCaseRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiagnosisFeeCollectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_manager_can_collect_the_diagnosis_fee_before_converting_to_a_repair(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $manager = StaffRecord::factory()->create();
        $manager->assignRole('Manager');
        $shop = ShopRecord::factory()->create();
        $this->grantShopAccess($manager, $shop->id);

        $case = DiagnosisCaseRecord::query()->create([
            'shop_id' => $shop->id,
            'created_by_staff_id' => $manager->id,
            'device_make' => 'Apple',
            'device_model' => 'iPhone 15 Pro',
            'device_lock_type' => 'none',
            'status' => 'diagnosed',
            'outcome' => 'repairable',
            'diagnosis_fee_minor' => 40000,
        ]);

        $response = $this->actingAs($manager, 'staff')->post("/admin/repairs/diagnosis-cases/{$case->id}/fee/collect", [
            'method' => 'cash',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('payments_transactions', [
            'payable_type' => 'diagnosis_case',
            'payable_id' => $case->id,
            'purpose' => 'repair_diagnosis_fee',
            'amount_minor' => 40000,
            'status' => 'confirmed',
        ]);

        $showResponse = $this->actingAs($manager, 'staff')->get("/admin/repairs/diagnosis-cases/{$case->id}");
        $showResponse->assertOk();
        $showResponse->assertInertia(fn ($page) => $page
            ->where('diagnosisCase.diagnosis_fee_paid', true)
            ->where('diagnosisCase.diagnosis_fee_payment_method', 'cash'));
    }

    public function test_collecting_the_fee_twice_is_rejected(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $manager = StaffRecord::factory()->create();
        $manager->assignRole('Manager');
        $shop = ShopRecord::factory()->create();
        $this->grantShopAccess($manager, $shop->id);

        $case = DiagnosisCaseRecord::query()->create([
            'shop_id' => $shop->id,
            'created_by_staff_id' => $manager->id,
            'device_make' => 'Apple',
            'device_model' => 'iPhone 15 Pro',
            'device_lock_type' => 'none',
            'status' => 'diagnosed',
            'outcome' => 'repairable',
            'diagnosis_fee_minor' => 40000,
        ]);

        $this->actingAs($manager, 'staff')->post("/admin/repairs/diagnosis-cases/{$case->id}/fee/collect", [
            'method' => 'cash',
        ])->assertRedirect();

        $secondResponse = $this->actingAs($manager, 'staff')->post("/admin/repairs/diagnosis-cases/{$case->id}/fee/collect", [
            'method' => 'cash',
        ]);

        $secondResponse->assertSessionHasErrors('method');
        $this->assertDatabaseCount('payments_transactions', 1);
    }

    public function test_a_staff_member_without_approve_payment_cannot_collect_the_fee(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier'); // no repairs.approve-payment per config/afprospos.php
        $shop = ShopRecord::factory()->create();
        $this->grantShopAccess($cashier, $shop->id);

        $case = DiagnosisCaseRecord::query()->create([
            'shop_id' => $shop->id,
            'created_by_staff_id' => $cashier->id,
            'device_make' => 'Apple',
            'device_model' => 'iPhone 15 Pro',
            'device_lock_type' => 'none',
            'status' => 'diagnosed',
            'outcome' => 'repairable',
            'diagnosis_fee_minor' => 40000,
        ]);

        $response = $this->actingAs($cashier, 'staff')->post("/admin/repairs/diagnosis-cases/{$case->id}/fee/collect", [
            'method' => 'cash',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('payments_transactions', 0);
    }
}
