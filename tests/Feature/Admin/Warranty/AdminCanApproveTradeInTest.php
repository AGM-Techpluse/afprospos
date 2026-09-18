<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Warranty;

use Database\Seeders\RolePermissionSeeder;
use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminCanApproveTradeInTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_record_assess_and_approve_a_trade_in(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $submitter = StaffRecord::factory()->create();
        $submitter->assignRole('Cashier');
        $assessor = StaffRecord::factory()->create();
        $assessor->assignRole('Technician');
        $approver = StaffRecord::factory()->create();
        $approver->assignRole('Technician');
        $customer = CustomerRecord::factory()->create();

        $store = $this->actingAs($submitter, 'staff')->post('/admin/warranty/trade-ins', [
            'customer_id' => $customer->id,
            'device_make' => 'Tecno',
            'device_model' => 'Camon 20',
            'device_imei' => '123456789012345',
            'device_condition' => 'Good condition, minor scratches.',
        ]);
        $store->assertRedirect();
        $this->assertDatabaseHas('trade_in_assessments', ['customer_id' => $customer->id, 'resolution_state' => 'submitted']);

        $tradeInId = (int) DB::table('trade_in_assessments')->latest('id')->value('id');

        $this->actingAs($assessor, 'staff')
            ->post("/admin/warranty/trade-ins/{$tradeInId}/assess", ['assessed_value_minor' => 35000000])
            ->assertRedirect();

        $this->assertDatabaseHas('trade_in_assessments', ['id' => $tradeInId, 'resolution_state' => 'assessed', 'assessed_value_minor' => 35000000]);

        $this->actingAs($approver, 'staff')
            ->post("/admin/warranty/trade-ins/{$tradeInId}/approve")
            ->assertRedirect();

        $this->assertDatabaseHas('trade_in_assessments', ['id' => $tradeInId, 'resolution_state' => 'approved']);
    }
}
