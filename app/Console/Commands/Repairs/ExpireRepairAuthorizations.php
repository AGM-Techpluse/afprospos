<?php

declare(strict_types=1);

namespace App\Console\Commands\Repairs;

use Domain\Repair\Application\Commands\ExpireRepairAuthorizationCommand;
use Domain\Repair\Application\Handlers\ExpireRepairAuthorizationHandler;
use Domain\Repair\Domain\Repositories\RepairJobRepository;
use Illuminate\Console\Command;

/** CPNC §2.2: parses CLI args, builds Commands, invokes the Handler, formats output — no business logic in the body itself. */
final class ExpireRepairAuthorizations extends Command
{
    protected $signature = 'afprospos:repairs:expire-authorizations {--limit=100}';

    protected $description = 'Flags repair jobs past their authorization/down-payment deadline as payment_overdue, then expired_cancelled one tick later.';

    public function __construct(
        private readonly RepairJobRepository $repairJobs,
        private readonly ExpireRepairAuthorizationHandler $handler,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $ids = $this->repairJobs->findExpirableAuthorizationIds((int) $this->option('limit'));

        if ($ids === []) {
            $this->info('No repair jobs to expire.');

            return self::SUCCESS;
        }

        foreach ($ids as $id) {
            $this->handler->handle(new ExpireRepairAuthorizationCommand($id));
        }

        $this->info(count($ids).' repair job(s) processed.');

        return self::SUCCESS;
    }
}
