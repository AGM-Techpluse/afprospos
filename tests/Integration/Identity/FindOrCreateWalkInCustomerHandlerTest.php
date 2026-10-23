<?php

declare(strict_types=1);

namespace Tests\Integration\Identity;

use Domain\Identity\Application\Commands\FindOrCreateWalkInCustomerCommand;
use Domain\Identity\Application\Handlers\FindOrCreateWalkInCustomerHandler;
use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FindOrCreateWalkInCustomerHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_a_passwordless_record_for_a_new_phone(): void
    {
        $id = app(FindOrCreateWalkInCustomerHandler::class)->handle(new FindOrCreateWalkInCustomerCommand(
            name: 'Chidi Okafor',
            phone: '08033334444',
            email: null,
        ));

        $this->assertDatabaseHas('customers', [
            'id' => $id->value,
            'name' => 'Chidi Okafor',
            'phone' => '08033334444',
        ]);
        $this->assertNull(CustomerRecord::query()->findOrFail($id->value)->password);
    }

    public function test_finds_an_existing_record_by_phone_instead_of_duplicating(): void
    {
        $existing = CustomerRecord::factory()->create(['phone' => '08055556666', 'name' => 'Original Name']);

        $id = app(FindOrCreateWalkInCustomerHandler::class)->handle(new FindOrCreateWalkInCustomerCommand(
            name: 'A Different Typed Name',
            phone: '08055556666',
            email: null,
        ));

        $this->assertSame($existing->id, $id->value);
        $this->assertSame(1, CustomerRecord::query()->where('phone', '08055556666')->count());
        // A found row's own details are left alone — not overwritten by a hastily-typed walk-in name.
        $this->assertSame('Original Name', $existing->fresh()->name);
    }
}
