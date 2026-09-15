<?php

declare(strict_types=1);

namespace App\Console\Commands\Collection;

use Domain\Collection\Application\Commands\AccrueStorageFeeCommand;
use Domain\Collection\Application\Commands\MarkCollectionAbandonedCommand;
use Domain\Collection\Application\Commands\MarkCollectionOverdueCommand;
use Domain\Collection\Application\Handlers\AccrueStorageFeeHandler;
use Domain\Collection\Application\Handlers\MarkCollectionAbandonedHandler;
use Domain\Collection\Application\Handlers\MarkCollectionOverdueHandler;
use Domain\Collection\Domain\Repositories\CollectionCaseRepository;
use Illuminate\Console\Command;

/** CPNC §2.2: parses CLI args, builds Commands, invokes Handlers, formats output — no business logic in the body itself. Runs overdue -> abandonment -> fee-accrual in one daily pass. */
final class ProcessCollectionDeadlines extends Command
{
    protected $signature = 'afprospos:collection:process-deadlines {--limit=200}';

    protected $description = 'Marks pending collection cases overdue, overdue cases abandoned, and accrues the daily storage fee for every open case.';

    public function __construct(
        private readonly CollectionCaseRepository $cases,
        private readonly MarkCollectionOverdueHandler $markOverdue,
        private readonly MarkCollectionAbandonedHandler $markAbandoned,
        private readonly AccrueStorageFeeHandler $accrueFee,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $overdueIds = $this->cases->findDueForOverdue($limit);
        foreach ($overdueIds as $id) {
            $this->markOverdue->handle(new MarkCollectionOverdueCommand($id));
        }

        $abandonedIds = $this->cases->findDueForAbandonment($limit);
        foreach ($abandonedIds as $id) {
            $this->markAbandoned->handle(new MarkCollectionAbandonedCommand($id));
        }

        $feeAccrualIds = $this->cases->findPendingOrOverdue($limit);
        foreach ($feeAccrualIds as $id) {
            $this->accrueFee->handle(new AccrueStorageFeeCommand($id));
        }

        $this->info(sprintf(
            '%d marked overdue, %d marked abandoned, %d fee-accrual checks.',
            count($overdueIds),
            count($abandonedIds),
            count($feeAccrualIds),
        ));

        return self::SUCCESS;
    }
}
