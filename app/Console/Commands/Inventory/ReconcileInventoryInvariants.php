<?php

declare(strict_types=1);

namespace App\Console\Commands\Inventory;

use Domain\Inventory\Application\Commands\ReconcileInventoryInvariantsCommand;
use Domain\Inventory\Application\Handlers\ReconcileInventoryInvariantsHandler;
use Illuminate\Console\Command;

final class ReconcileInventoryInvariants extends Command
{
    protected $signature = 'afprospos:inventory:reconcile';

    protected $description = 'Validates DBDD §27 inventory invariants (0 <= reserved <= on_hand; a reserved item always has both reserved_by_type and reserved_by_id).';

    public function __construct(
        private readonly ReconcileInventoryInvariantsHandler $handler,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $violations = $this->handler->handle(new ReconcileInventoryInvariantsCommand);

        if ($violations === []) {
            $this->info('No invariant violations found.');

            return self::SUCCESS;
        }

        $this->error(count($violations).' invariant violation(s) found:');
        $this->table(
            ['Subject', 'ID', 'Description'],
            array_map(static fn ($violation): array => [$violation->subjectType, $violation->subjectId, $violation->description], $violations),
        );

        return self::FAILURE;
    }
}
