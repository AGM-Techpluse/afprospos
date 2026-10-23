<?php

declare(strict_types=1);

namespace Tests\Feature\Identity;

use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_customer_can_register_and_is_logged_in(): void
    {
        $response = $this->post('/customer/register', [
            'name' => 'Amina Bello',
            'phone' => '08012345678',
            'email' => 'amina@example.com',
            'password' => 'Sup3rSecret!',
            'password_confirmation' => 'Sup3rSecret!',
        ]);

        $response->assertRedirect(route('customer.dashboard'));
        $this->assertAuthenticated('customer');
        $this->assertDatabaseHas('customers', ['phone' => '08012345678', 'name' => 'Amina Bello']);
    }

    public function test_registering_with_a_phone_already_walked_in_claims_that_record_instead_of_rejecting_it(): void
    {
        $walkIn = CustomerRecord::factory()->walkIn()->create(['phone' => '08099998888', 'name' => 'Tunde Walk-in']);

        $response = $this->post('/customer/register', [
            'name' => 'Tunde Adeyemi',
            'phone' => '08099998888',
            'email' => 'tunde@example.com',
            'password' => 'Sup3rSecret!',
            'password_confirmation' => 'Sup3rSecret!',
        ]);

        $response->assertRedirect(route('customer.dashboard'));
        $this->assertAuthenticated('customer');

        // Same row — claimed, not duplicated.
        $this->assertSame(1, CustomerRecord::query()->where('phone', '08099998888')->count());
        $walkIn->refresh();
        $this->assertSame('Tunde Adeyemi', $walkIn->name);
        $this->assertNotNull($walkIn->password);
    }

    public function test_registering_with_a_phone_already_registered_is_rejected_as_a_duplicate(): void
    {
        CustomerRecord::factory()->create(['phone' => '08011112222']);

        $response = $this->post('/customer/register', [
            'name' => 'Someone Else',
            'phone' => '08011112222',
            'email' => 'someone@example.com',
            'password' => 'Sup3rSecret!',
            'password_confirmation' => 'Sup3rSecret!',
        ]);

        $response->assertSessionHasErrors('phone');
        $this->assertGuest('customer');
    }

    public function test_a_walk_in_record_with_no_password_cannot_log_in(): void
    {
        CustomerRecord::factory()->walkIn()->create(['email' => 'walkin@example.com']);

        $response = $this->post('/customer/login', [
            'email' => 'walkin@example.com',
            'password' => 'anything',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest('customer');
    }

    public function test_a_registered_customer_can_still_log_in_normally(): void
    {
        CustomerRecord::factory()->create(['email' => 'registered@example.com', 'password' => Hash::make('Sup3rSecret!')]);

        $response = $this->post('/customer/login', [
            'email' => 'registered@example.com',
            'password' => 'Sup3rSecret!',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticated('customer');
    }
}
