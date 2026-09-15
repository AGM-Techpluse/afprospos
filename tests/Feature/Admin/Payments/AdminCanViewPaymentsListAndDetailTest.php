<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Payments;

use Database\Seeders\RolePermissionSeeder;
use Domain\Payments\Infrastructure\Persistence\Eloquent\PaymentTransactionRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCanViewPaymentsListAndDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_accountant_can_view_the_payments_list_and_a_single_payments_detail(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $accountant = StaffRecord::factory()->create();
        $accountant->assignRole('Accountant');

        $transaction = PaymentTransactionRecord::factory()->pendingConfirmation()->create();

        $indexResponse = $this->actingAs($accountant, 'staff')->get('/admin/payments');
        $indexResponse->assertOk();
        $indexResponse->assertInertia(fn ($page) => $page->component('Admin/Payments/Index'));

        $showResponse = $this->actingAs($accountant, 'staff')->get("/admin/payments/{$transaction->id}");
        $showResponse->assertOk();
        $showResponse->assertInertia(fn ($page) => $page->component('Admin/Payments/Show')->where('payment.id', $transaction->id));
    }

    public function test_a_staff_member_without_payments_view_cannot_see_the_list(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');

        $response = $this->actingAs($cashier, 'staff')->get('/admin/payments');

        $response->assertForbidden();
    }
}
