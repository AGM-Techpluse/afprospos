<?php

declare(strict_types=1);

use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('refuses login for a deactivated staff account', function (): void {
    $staff = StaffRecord::factory()->deactivated()->create([
        'email' => '[email protected]',
        'password' => Hash::make('correct-password'),
    ]);

    $response = $this->post('/staff/login', [
        'email' => '[email protected]',
        'password' => 'correct-password',
    ]);

    $response->assertSessionHasErrors('email');
    // The "Cannot" assertion the CPNC requires: no state mutation, i.e.
    // no session was actually established for this account.
    expect(Auth::guard('staff')->check())->toBeFalse();
    unset($staff);
});
