<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Staff;

use Database\Seeders\RolePermissionSeeder;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffCanChangeOwnPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_lets_a_staff_member_change_their_own_password(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');

        $response = $this->actingAs($cashier, 'staff')->post('/admin/account/password', [
            'current_password' => 'password',
            'password' => 'a-new-strong-password',
            'password_confirmation' => 'a-new-strong-password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('a-new-strong-password', $cashier->fresh()->password));
    }

    public function test_rejects_the_wrong_current_password_and_leaves_the_password_unchanged(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        $originalHash = $cashier->password;

        $response = $this->actingAs($cashier, 'staff')->post('/admin/account/password', [
            'current_password' => 'not-the-right-password',
            'password' => 'a-new-strong-password',
            'password_confirmation' => 'a-new-strong-password',
        ]);

        $response->assertSessionHasErrors('current_password');
        $this->assertSame($originalHash, $cashier->fresh()->password);
    }

    public function test_rejects_an_unconfirmed_new_password(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cashier = StaffRecord::factory()->create();
        $cashier->assignRole('Cashier');
        $originalHash = $cashier->password;

        $response = $this->actingAs($cashier, 'staff')->post('/admin/account/password', [
            'current_password' => 'password',
            'password' => 'a-new-strong-password',
            'password_confirmation' => 'does-not-match',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertSame($originalHash, $cashier->fresh()->password);
    }

    public function test_guests_cannot_change_a_staff_members_password(): void
    {
        $cashier = StaffRecord::factory()->create();

        $response = $this->post('/admin/account/password', [
            'current_password' => 'password',
            'password' => 'a-new-strong-password',
            'password_confirmation' => 'a-new-strong-password',
        ]);

        $response->assertRedirect('/staff/login');
        $this->assertTrue(Hash::check('password', $cashier->fresh()->password));
    }
}
