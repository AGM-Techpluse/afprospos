<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use App\Support\Transactions\Atomic;
use Carbon\CarbonImmutable;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Repair\Application\Commands\RequestSupplementalQuoteCommand;
use Domain\Repair\Domain\Entities\RepairSupplementalQuote;
use Domain\Repair\Domain\Events\SupplementalQuoteRequested;
use Domain\Repair\Domain\Repositories\RepairJobRepository;
use Domain\Repair\Domain\Repositories\RepairSupplementalQuoteRepository;
use Domain\Repair\Domain\ValueObjects\RepairJobId;
use Domain\Shared\Domain\ValueObjects\Money;
use Domain\Shared\Domain\ValueObjects\StaffId;
use Illuminate\Support\Facades\Event;

final class RequestSupplementalQuoteHandler
{
    public function __construct(
        private readonly RepairJobRepository $repairJobs,
        private readonly RepairSupplementalQuoteRepository $quotes,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(RequestSupplementalQuoteCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $jobId = new RepairJobId($command->repairJobId);
            $job = $this->repairJobs->lockForUpdate($jobId);
            $job->assertSupplementalQuoteRequestable();

            $quote = RepairSupplementalQuote::request(
                $jobId,
                new Money($command->amountMinor),
                $command->reason,
                $command->requestedByStaffId,
            );
            $id = $this->quotes->save($quote);

            $this->audit->record(
                module: 'Repair',
                eventType: 'SupplementalQuoteRequested',
                actorStaffId: new StaffId($command->requestedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'repair_supplemental_quote',
                subjectId: $id->value,
                beforeState: null,
                afterState: ['amount_minor' => $command->amountMinor, 'reason' => $command->reason],
            );

            Event::dispatch(new SupplementalQuoteRequested($jobId->value, $job->shopId()->value, $command->requestedByStaffId, CarbonImmutable::now()));
        });
    }
}
