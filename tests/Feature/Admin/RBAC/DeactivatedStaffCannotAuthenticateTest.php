<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\RBAC;

use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DeactivatedStaffCannotAuthenticateTest extends TestCase
{
    use RefreshDatabase;

    public function test_refuses_login_for_a_deactivated_staff_account(): void
    {
        $staff = StaffRecord::factory()->deactivated()->create([
            'email' => 'deactivated-staff@afprospos.test',
            'password' => Hash::make('correct-password'),
        ]);

        $response = $this->post('/staff/login', [
            'email' => 'deactivated-staff@afprospos.test',
            'password' => 'correct-password',
        ]);

        $response->assertSessionHasErrors('auth');
        // The "Cannot" assertion the CPNC requires: no state mutation, i.e.
        // no session was actually established for this account.
        $this->assertFalse(Auth::guard('staff')->check());
        unset($staff);
    }
}
