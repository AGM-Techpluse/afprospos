<?php

declare(strict_types=1);

namespace Database\Factories;

use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/** @extends Factory<StaffRecord> */
final class StaffRecordFactory extends Factory
{
    protected $model = StaffRecord::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'status' => 'active',
        ];
    }

    public function deactivated(): self
    {
        return $this->state(fn (): array => ['status' => 'deactivated']);
    }
}
