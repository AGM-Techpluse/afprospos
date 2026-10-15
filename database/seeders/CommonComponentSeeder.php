<?php

declare(strict_types=1);

namespace Database\Seeders;

use Domain\Repair\Infrastructure\Persistence\Eloquent\CommonComponentRecord;
use Illuminate\Database\Seeder;

/** Backfills the quick-check list that used to be a hardcoded frontend array (CommonComponentsCard.tsx) now that it's an admin-editable catalog. Idempotent (firstOrCreate by label) so it's safe to rerun on every deploy, same pattern as RolePermissionSeeder. */
final class CommonComponentSeeder extends Seeder
{
    private const LABELS = [
        'Screen / display',
        'Battery',
        'Camera',
        'Speaker',
        'Microphone',
        'Charging port',
        'Power button',
        'Volume buttons',
        'SIM tray',
        'Vibration motor',
        'Wi-Fi / Bluetooth',
        'Back cover / housing',
    ];

    public function run(): void
    {
        foreach (self::LABELS as $index => $label) {
            CommonComponentRecord::query()->firstOrCreate(['label' => $label], ['sort_order' => $index]);
        }
    }
}
