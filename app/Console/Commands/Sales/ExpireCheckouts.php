<?php

declare(strict_types=1);

namespace App\Console\Commands\Sales;

use Domain\Sales\Application\Commands\ExpireCheckoutCommand;
use Domain\Sales\Application\Handlers\ExpireCheckoutHandler;
use Domain\Sales\Domain\Repositories\SalesCheckoutRepository;
use Illuminate\Console\Command;

/** CPNC §2.2: parses CLI args, builds Commands, invokes the Handler, formats output — no business logic in the body itself. */
final class ExpireCheckouts extends Command
{
    protected $signature = 'afprospos:sales:expire-checkouts {--limit=100}';

    protected $description = 'Expires open checkouts whose reservation window has passed and releases their inventory reservations (DBDD §26, SALE/INV-BR-06).';

    public function __construct(
        private readonly SalesCheckoutRepository $checkouts,
        private readonly ExpireCheckoutHandler $handler,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $ids = $this->checkouts->findExpirableIds((int) $this->option('limit'));

        if ($ids === []) {
            $this->info('No expirable checkouts found.');

            return self::SUCCESS;
        }

        foreach ($ids as $id) {
            $this->handler->handle(new ExpireCheckoutCommand($id));
        }

        $this->info(count($ids).' checkout(s) expired.');

        return self::SUCCESS;
    }
}
