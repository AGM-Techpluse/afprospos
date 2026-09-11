<?php

declare(strict_types=1);

namespace Tests\Feature\Customer\Profile;

use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerCanUpdateProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_update_their_profile(): void
    {
        $customer = CustomerRecord::factory()->create([
            'phone' => '08011111111',
            'email' => 'original@example.test',
        ]);

        $response = $this->actingAs($customer, 'customer')->put('/customer/profile', [
            'name' => 'Updated Name',
            'phone' => '08022222222',
            'email' => 'updated@example.test',
        ]);

        $response->assertRedirect(route('customer.profile.show'));
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Updated Name',
            'phone' => '08022222222',
            'email' => 'updated@example.test',
        ]);
    }

    public function test_customer_cannot_update_profile_with_a_phone_already_used_by_another_customer(): void
    {
        CustomerRecord::factory()->create(['phone' => '08033333333']);
        $customer = CustomerRecord::factory()->create([
            'phone' => '08011111111',
            'name' => 'Original Name',
        ]);

        $response = $this->actingAs($customer, 'customer')->put('/customer/profile', [
            'name' => 'Updated Name',
            'phone' => '08033333333',
            'email' => $customer->email,
        ]);

        $response->assertSessionHasErrors('phone');
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Original Name',
            'phone' => '08011111111',
        ]);
    }

    public function test_customer_cannot_update_profile_with_an_email_already_used_by_another_customer(): void
    {
        CustomerRecord::factory()->create(['email' => 'taken@example.test']);
        $customer = CustomerRecord::factory()->create([
            'phone' => '08011111111',
            'name' => 'Original Name',
        ]);

        $response = $this->actingAs($customer, 'customer')->put('/customer/profile', [
            'name' => 'Updated Name',
            'phone' => $customer->phone,
            'email' => 'taken@example.test',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Original Name',
        ]);
    }
}
