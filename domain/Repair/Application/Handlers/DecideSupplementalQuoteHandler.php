<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use App\Support\Transactions\Atomic;
use Carbon\CarbonImmutable;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Repair\Application\Commands\DecideSupplementalQuoteCommand;
use Domain\Repair\Domain\Repositories\RepairSupplementalQuoteRepository;
use Domain\Repair\Domain\ValueObjects\RepairSupplementalQuoteId;
use Domain\Shared\Domain\ValueObjects\StaffId;

/**
 * Unlike `DecideRepairPaymentApprovalHandler`/`DecideRepairDownPaymentApprovalHandler`,
 * approval here doesn't initiate a `payments_transactions` row — no money
 * has changed hands yet. It just makes the amount count toward
 * `RepairJobDetailQuery`'s `quote_total_minor`/`outstanding_balance_minor`
 * and `RequestRepairPaymentApprovalHandler::expectedOutstandingMinor()`,
 * so the *next* payment request (down payment or final) picks up the
 * corrected total automatically.
 */
final class DecideSupplementalQuoteHandler
{
    public function __construct(
        private readonly RepairSupplementalQuoteRepository $quotes,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(DecideSupplementalQuoteCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $id = new RepairSupplementalQuoteId($command->quoteId);
            $quote = $this->quotes->lockForUpdate($id);
            $now = CarbonImmutable::now();

            if ($command->approved) {
                $quote->approve($command->decidedByStaffId, $now);
            } else {
                $quote->reject($command->decidedByStaffId, $now);
            }

            $this->quotes->save($quote);

            $this->audit->record(
                module: 'Repair',
                eventType: $command->approved ? 'SupplementalQuoteApproved' : 'SupplementalQuoteRejected',
                actorStaffId: new StaffId($command->decidedByStaffId),
                actorRoleSnapshot: null,
                subjectType: 'repair_supplemental_quote',
                subjectId: $id->value,
                beforeState: null,
                afterState: ['status' => $quote->status()],
            );
        });
    }
}
