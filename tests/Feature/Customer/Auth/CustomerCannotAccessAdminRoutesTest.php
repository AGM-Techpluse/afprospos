<?php

declare(strict_types=1);

namespace Tests\Feature\Customer\Auth;

use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerCannotAccessAdminRoutesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * BRD RBAC-07: "Customers shall be restricted to the Customer Dashboard
     * and shall not have access to any admin module." A customer session
     * exists under the `customer` guard only — `auth:staff` on every /admin
     * route must reject it outright, never fall through by treating any
     * authenticated user as sufficient.
     */
    public function test_never_lets_a_customer_session_reach_an_admin_route(): void
    {
        $customer = CustomerRecord::factory()->create();

        $response = $this->actingAs($customer, 'customer')->get('/admin/dashboard');

        $response->assertRedirect(route('staff.login'));
    }

    public function test_never_lets_a_customer_session_assign_a_staff_role(): void
    {
        $customer = CustomerRecord::factory()->create();

        $response = $this->actingAs($customer, 'customer')
            ->post('/admin/staff/1/roles', ['role_name' => 'Technician']);

        $response->assertRedirect(route('staff.login'));
    }
}
