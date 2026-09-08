<?php

declare(strict_types=1);

use Database\Seeders\RolePermissionSeeder;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lets an admin with staff.assign assign a role to another staff member', function (): void {
    $this->seed(RolePermissionSeeder::class);

    $owner = StaffRecord::factory()->create();
    $owner->assignRole('Shop Owner');

    $target = StaffRecord::factory()->create();

    $response = $this->actingAs($owner, 'staff')
        ->post("/admin/staff/{$target->id}/roles", ['role_name' => 'Technician']);

    $response->assertRedirect();
    expect($target->fresh()->hasRole('Technician'))->toBeTrue();

    $this->assertDatabaseHas('audit_logs', [
        'module' => 'RBAC',
        'event_type' => 'RoleAssignedToStaff',
        'subject_type' => 'staff',
        'subject_id' => $target->id,
    ]);
});

it('blocks a staff member without staff.assign from assigning a role', function (): void {
    $this->seed(RolePermissionSeeder::class);

    $cashier = StaffRecord::factory()->create();
    $cashier->assignRole('Cashier'); // has no staff.* permissions per config/afprospos.php

    $target = StaffRecord::factory()->create();

    $response = $this->actingAs($cashier, 'staff')
        ->post("/admin/staff/{$target->id}/roles", ['role_name' => 'Technician']);

    $response->assertForbidden();
    expect($target->fresh()->hasRole('Technician'))->toBeFalse();
});
