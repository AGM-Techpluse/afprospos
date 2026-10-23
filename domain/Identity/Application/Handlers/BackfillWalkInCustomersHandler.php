<?php

declare(strict_types=1);

namespace Domain\Identity\Application\Handlers;

use Domain\Identity\Application\Commands\FindOrCreateWalkInCustomerCommand;
use Illuminate\Support\Facades\DB;

/**
 * One-time (idempotent) backfill: a walk-in recorded before unified
 * customer records existed is only free-text name/phone/email scattered
 * across four tables — this ensures each distinct phone number among
 * them has a real (passwordless) `CustomerRecord`, so they're findable
 * via `CustomerDirectoryQuery::search()` from here on.
 *
 * Deliberately does NOT touch `customer_id` on the historical
 * sales_checkouts/sales_sales/repair_jobs/diagnosis_cases rows
 * themselves — repointing an already-settled historical record at a
 * newly-created customer risks silently changing any business logic
 * elsewhere that branches on "customer_id is null" for that record
 * (receipts, warranty eligibility, etc.), which hasn't been audited.
 * The goal here is only "this person is now findable," not rewriting
 * history.
 */
final class BackfillWalkInCustomersHandler
{
    private const TABLES = ['sales_checkouts', 'sales_sales', 'repair_jobs', 'diagnosis_cases'];

    public function __construct(private readonly FindOrCreateWalkInCustomerHandler $findOrCreate) {}

    /** @return array{found: int, created: int} */
    public function handle(): array
    {
        $byPhone = [];

        foreach (self::TABLES as $table) {
            $rows = DB::table($table)
                ->whereNull('customer_id')
                ->whereNotNull('walk_in_phone')
                ->select('walk_in_name', 'walk_in_phone', 'walk_in_email')
                ->orderBy('id')
                ->cursor();

            foreach ($rows as $row) {
                $byPhone[$row->walk_in_phone] ??= [
                    'name' => $row->walk_in_name,
                    'email' => $row->walk_in_email,
                ];
            }
        }

        $created = 0;

        foreach ($byPhone as $phone => $contact) {
            $existedBefore = DB::table('customers')->where('phone', $phone)->exists();

            $this->findOrCreate->handle(new FindOrCreateWalkInCustomerCommand(
                name: $contact['name'] ?? $phone,
                phone: $phone,
                email: $contact['email'],
            ));

            if (! $existedBefore) {
                $created++;
            }
        }

        return ['found' => count($byPhone), 'created' => $created];
    }
}
