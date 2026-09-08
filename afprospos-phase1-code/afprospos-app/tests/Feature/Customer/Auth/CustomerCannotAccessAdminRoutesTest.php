<?php

declare(strict_types=1);

use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * BRD RBAC-07: "Customers shall be restricted to the Customer Dashboard
 * and shall not have access to any admin module." A customer session
 * exists under the `customer` guard only — `auth:staff` on every /admin
 * route must reject it outright, never fall through by treating any
 * authenticated user as sufficient.
 */
it('never lets a customer session reach an admin route', function (): void {
    $customer = CustomerRecord::factory()->create();

    $response = $this->actingAs($customer, 'customer')->get('/admin/dashboard');

    $response->assertRedirect(route('staff.login'));
});

it('never lets a customer session assign a staff role', function (): void {
    $customer = CustomerRecord::factory()->create();

    $response = $this->actingAs($customer, 'customer')
        ->post('/admin/staff/1/roles', ['role_name' => 'Technician']);

    $response->assertRedirect(route('staff.login'));
});
