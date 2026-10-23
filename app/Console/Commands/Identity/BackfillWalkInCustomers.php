<?php

declare(strict_types=1);

namespace App\Console\Commands\Identity;

use Domain\Identity\Application\Handlers\BackfillWalkInCustomersHandler;
use Illuminate\Console\Command;

/** CPNC §2.2: parses CLI args, builds one Command, invokes one Handler, formats output — no business logic in the body itself. */
final class BackfillWalkInCustomers extends Command
{
    protected $signature = 'afprospos:customers:backfill-walk-ins';

    protected $description = 'Creates a real (passwordless) customer record for every distinct walk-in phone number found across past sales, repairs, and diagnosis cases, so they are findable on a later visit. Safe to run more than once.';

    public function __construct(private readonly BackfillWalkInCustomersHandler $handler)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $result = $this->handler->handle();

        $this->info("Found {$result['found']} distinct walk-in phone numbers — created {$result['created']} new customer record(s).");

        return self::SUCCESS;
    }
}
