<?php

declare(strict_types=1);

namespace Database\Factories;

use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/** @extends Factory<CustomerRecord> */
final class CustomerRecordFactory extends Factory
{
    protected $model = CustomerRecord::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => fake()->unique()->phoneNumber(),
            'email' => fake()->safeEmail(),
            'password' => Hash::make('password'),
            'notification_preferences' => ['whatsapp' => true, 'email' => true, 'in_app' => true],
            'marketing_opt_out' => false,
            'status' => 'active',
        ];
    }
}
