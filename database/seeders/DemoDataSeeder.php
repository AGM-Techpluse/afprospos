<?php

declare(strict_types=1);

namespace Database\Seeders;

use Domain\RBAC\Infrastructure\Persistence\Eloquent\StaffShopGrantRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\CustomerRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Optional demo/dev data — extra shops, staff across every role, and
 * customers — so the app has something to click through beyond the
 * single bootstrap shop+owner DatabaseSeeder creates. Not part of the
 * default `db:seed` run; invoke explicitly:
 *   php artisan db:seed --class="Database\Seeders\DemoDataSeeder"
 * Every demo staff account shares the password below — change/remove
 * this data before any shared/staging deployment.
 */
final class DemoDataSeeder extends Seeder
{
    private const DEMO_PASSWORD = 'password';

    public function run(): void
    {
        $mainShop = ShopRecord::query()->firstOrCreate(
            ['sku_prefix_code' => 'MAIN'],
            [
                'name' => 'Main Shop',
                'address' => 'Set your real address',
                'contact_phone' => '+2340000000000',
                'contact_email' => 'owner@afprospos.test',
                'offline_policy' => ['cash_sales' => true, 'bank_transfer_confirmation' => false],
                'status' => 'active',
            ],
        );

        $ikeja = $this->shop('IKJ', 'Ikeja Branch', '14 Allen Avenue, Ikeja, Lagos', '+2348011112222', 'ikeja@afprospos.test');
        $abuja = $this->shop('ABJ', 'Abuja Branch', 'Plot 22 Wuse II, Abuja', '+2348033334444', 'abuja@afprospos.test');

        $owner = StaffRecord::query()->where('email', 'owner@afprospos.test')->first();

        $accountant = $this->staff('Chidinma Okafor', '+2348055556666', 'accountant@afprospos.test', 'Accountant', [$mainShop, $ikeja, $abuja], $owner);
        $technicianA = $this->staff('Emeka Nwosu', '+2348077778888', 'technician1@afprospos.test', 'Technician', [$mainShop], $owner);
        $technicianB = $this->staff('Bola Adeyemi', '+2348099990000', 'technician2@afprospos.test', 'Technician', [$ikeja], $owner);
        $cashierA = $this->staff('Grace Effiong', '+2348122223333', 'cashier1@afprospos.test', 'Cashier', [$mainShop], $owner);
        $cashierB = $this->staff('Musa Ibrahim', '+2348144445555', 'cashier2@afprospos.test', 'Cashier', [$abuja], $owner);
        $productStaff = $this->staff('Ifeoma Chukwu', '+2348166667777', 'productstaff@afprospos.test', 'Product Staff', [$mainShop, $ikeja], $owner);
        $marketingStaff = $this->staff('Tunde Bakare', '+2348188889999', 'marketing@afprospos.test', 'Marketing Staff', [$mainShop], $owner);

        $customers = [
            ['name' => 'Amaka Okonkwo', 'phone' => '+2348012340001', 'email' => 'amaka.okonkwo@example.com'],
            ['name' => 'David Eze', 'phone' => '+2348012340002', 'email' => 'david.eze@example.com'],
            ['name' => 'Fatima Bello', 'phone' => '+2348012340003', 'email' => 'fatima.bello@example.com'],
            ['name' => 'Segun Adekunle', 'phone' => '+2348012340004', 'email' => 'segun.adekunle@example.com'],
            ['name' => 'Ngozi Umeh', 'phone' => '+2348012340005', 'email' => 'ngozi.umeh@example.com'],
            ['name' => 'Chuka Obi', 'phone' => '+2348012340006', 'email' => 'chuka.obi@example.com'],
            ['name' => 'Halima Yusuf', 'phone' => '+2348012340007', 'email' => 'halima.yusuf@example.com'],
            ['name' => 'Peter Okoro', 'phone' => '+2348012340008', 'email' => 'peter.okoro@example.com'],
        ];

        foreach ($customers as $customer) {
            CustomerRecord::query()->firstOrCreate(
                ['phone' => $customer['phone']],
                [
                    'name' => $customer['name'],
                    'email' => $customer['email'],
                    'password' => Hash::make(self::DEMO_PASSWORD),
                    'notification_preferences' => ['whatsapp' => true, 'email' => true, 'in_app' => true],
                    'marketing_opt_out' => false,
                    'status' => 'active',
                ],
            );
        }

        $this->command?->info('Demo shops: Main Shop (MAIN), Ikeja Branch (IKJ), Abuja Branch (ABJ).');
        $this->command?->info('Demo staff password for every account below: '.self::DEMO_PASSWORD);
        $this->command?->table(['Name', 'Email', 'Role'], [
            ['Chidinma Okafor', 'accountant@afprospos.test', 'Accountant'],
            ['Emeka Nwosu', 'technician1@afprospos.test', 'Technician'],
            ['Bola Adeyemi', 'technician2@afprospos.test', 'Technician'],
            ['Grace Effiong', 'cashier1@afprospos.test', 'Cashier'],
            ['Musa Ibrahim', 'cashier2@afprospos.test', 'Cashier'],
            ['Ifeoma Chukwu', 'productstaff@afprospos.test', 'Product Staff'],
            ['Tunde Bakare', 'marketing@afprospos.test', 'Marketing Staff'],
        ]);
        $this->command?->info(count($customers).' demo customers created (same demo password).');
    }

    private function shop(string $skuPrefixCode, string $name, string $address, string $phone, string $email): ShopRecord
    {
        return ShopRecord::query()->firstOrCreate(
            ['sku_prefix_code' => $skuPrefixCode],
            [
                'name' => $name,
                'address' => $address,
                'contact_phone' => $phone,
                'contact_email' => $email,
                'offline_policy' => ['cash_sales' => true, 'bank_transfer_confirmation' => false],
                'status' => 'active',
            ],
        );
    }

    /** @param  ShopRecord[]  $shops */
    private function staff(string $name, string $phone, string $email, string $role, array $shops, ?StaffRecord $grantedBy): StaffRecord
    {
        $staff = StaffRecord::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'phone' => $phone,
                'password' => Hash::make(self::DEMO_PASSWORD),
                'status' => 'active',
            ],
        );

        if (! $staff->hasRole($role)) {
            $staff->assignRole($role);
        }

        foreach ($shops as $shop) {
            StaffShopGrantRecord::query()->firstOrCreate(
                ['staff_id' => $staff->id, 'shop_id' => $shop->id, 'revoked_at' => null],
                ['granted_by_staff_id' => $grantedBy?->id, 'granted_at' => now()],
            );
        }

        return $staff;
    }
}
