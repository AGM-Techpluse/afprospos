<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use App\Support\Transactions\Atomic;
use Carbon\CarbonImmutable;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Payments\Application\Commands\InitiatePaymentCommand;
use Domain\Payments\Application\Contracts\PaymentInitiationService;
use Domain\Repair\Application\Commands\AuthorizeRepairCommand;
use Domain\Repair\Domain\Repositories\RepairJobRepository;
use Domain\Repair\Domain\ValueObjects\RepairJobId;
use Domain\Shared\Domain\ValueObjects\Money;
use Domain\Shared\Domain\ValueObjects\StaffId;

/**
 * `down_payment_deadline_at` doubles as the authorization-response
 * deadline (Phase 6 scope decision) — set here regardless of whether a
 * down payment is actually required, so a zero-down-payment repair still
 * has a response window before ExpireRepairAuthorizations can cancel it.
 */
final class AuthorizeRepairHandler
{
    public function __construct(
        private readonly RepairJobRepository $repairJobs,
        private readonly PaymentInitiationService $payments,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(AuthorizeRepairCommand $command): void
    {
        $this->atomic->run(function () use ($command): void {
            $jobId = new RepairJobId($command->repairJobId);
            $job = $this->repairJobs->lockForUpdate($jobId);
            $actor = new StaffId($command->authorizedByStaffId);

            $deadline = CarbonImmutable::now()->addHours((int) config('afprospos.repairs.authorization_response_hours'));
            $job->setAuthorizationRequirements(
                $command->downPaymentRequiredMinor > 0 ? new Money($command->downPaymentRequiredMinor) : null,
                $deadline,
            );

            if ($command->downPaymentRequiredMinor === 0) {
                $job->grantAuthorization();
            } else {
                $this->payments->initiate(new InitiatePaymentCommand(
                    payableType: 'repair_job',
                    payableId: $jobId->value,
                    method: $command->downPaymentMethod,
                    amountMinor: $command->downPaymentRequiredMinor,
                    initiatedByStaffId: $command->authorizedByStaffId,
                ));
            }

            $this->repairJobs->save($job);

            $this->audit->record(
                module: 'Repair',
                eventType: 'RepairAuthorized',
                actorStaffId: $actor,
                actorRoleSnapshot: null,
                subjectType: 'repair_job',
                subjectId: $jobId->value,
                beforeState: null,
                afterState: [
                    'down_payment_required_minor' => $command->downPaymentRequiredMinor,
                    'down_payment_deadline_at' => $deadline->toDateTimeString(),
                    'status' => $job->status(),
                ],
            );
        });
    }
}
