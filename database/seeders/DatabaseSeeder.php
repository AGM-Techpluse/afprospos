<?php

declare(strict_types=1);

namespace Database\Seeders;

use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        // Bootstrap shop + owner account so `php artisan migrate:fresh
        // --seed` produces an immediately loggable-in environment. Change
        // this password before any shared/staging deployment.
        $shop = ShopRecord::query()->firstOrCreate(
            ['sku_prefix_code' => 'MAIN'],
            [
                'name' => 'Main Shop',
                'address' => 'Set your real address',
                'contact_phone' => '+2340000000000',
                'contact_email' => '[email protected]',
                'offline_policy' => ['cash_sales' => true, 'bank_transfer_confirmation' => false],
                'status' => 'active',
            ],
        );

        $owner = StaffRecord::query()->firstOrCreate(
            ['email' => '[email protected]'],
            [
                'name' => 'Shop Owner',
                'phone' => '+2340000000001',
                'password' => Hash::make('password'),
                'status' => 'active',
            ],
        );

        if (! $owner->hasRole('Shop Owner')) {
            $owner->assignRole('Shop Owner');
        }
    }
}
