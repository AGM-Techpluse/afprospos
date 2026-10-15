<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use App\Support\Transactions\Atomic;
use Carbon\CarbonImmutable;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Payments\Application\Commands\InitiatePaymentCommand;
use Domain\Payments\Application\Contracts\PaymentInitiationService;
use Domain\Repair\Application\Commands\AuthorizeRepairCommand;
use Domain\Repair\Domain\Entities\RepairDiagnosisFee;
use Domain\Repair\Domain\Repositories\RepairAgreementRepository;
use Domain\Repair\Domain\Repositories\RepairDiagnosisFeeRepository;
use Domain\Repair\Domain\Repositories\RepairJobRepository;
use Domain\Repair\Domain\ValueObjects\RepairJobId;
use Domain\Shared\Domain\ValueObjects\Money;
use Domain\Shared\Domain\ValueObjects\StaffId;

/**
 * The manager's single quote & authorization action: sets the quote amount,
 * diagnosis fee, down payment requirement, and any special agreement, then
 * authorizes the repair immediately.
 */
final class AuthorizeRepairHandler
{
    public function __construct(
        private readonly RepairJobRepository $repairJobs,
        private readonly RepairAgreementRepository $agreements,
        private readonly RepairDiagnosisFeeRepository $fees,
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

            $job->setRepairCost(new Money($command->repairCostMinor));
            $fee = $this->fees->find($jobId);

            if ($command->diagnosisFeeMinor !== null) {
                if ($fee === null) {
                    $fee = RepairDiagnosisFee::set($jobId, $command->diagnosisFeeMinor, $command->authorizedByStaffId);
                } else {
                    $fee->changeAmount($command->diagnosisFeeMinor, $command->authorizedByStaffId);
                }

                $this->fees->save($fee);
            }

            $deadline = CarbonImmutable::now()->addHours((int) config('afprospos.repairs.authorization_response_hours'));
            $job->setAuthorizationRequirements(
                $command->downPaymentRequiredMinor > 0 ? new Money($command->downPaymentRequiredMinor) : null,
                $deadline,
            );
            $job->grantAuthorization();

            $this->repairJobs->save($job);

            if ($command->downPaymentRequiredMinor > 0 && $command->downPaymentMethod !== null) {
                $this->payments->initiate(new InitiatePaymentCommand(
                    payableType: 'repair_job',
                    payableId: $jobId->value,
                    purpose: 'repair_down_payment',
                    method: $command->downPaymentMethod,
                    amountMinor: $command->downPaymentRequiredMinor,
                    initiatedByStaffId: $command->authorizedByStaffId,
                ));
            }

            if ($command->specialAgreementNote !== null && trim($command->specialAgreementNote) !== '') {
                $this->agreements->record($jobId->value, trim($command->specialAgreementNote), $command->authorizedByStaffId);
            }

            $this->audit->record(
                module: 'Repair',
                eventType: 'RepairAuthorized',
                actorStaffId: $actor,
                actorRoleSnapshot: null,
                subjectType: 'repair_job',
                subjectId: $jobId->value,
                beforeState: null,
                afterState: [
                    'repair_cost_minor' => $command->repairCostMinor,
                    'diagnosis_fee_minor' => $fee?->amountMinor(),
                    'down_payment_required_minor' => $command->downPaymentRequiredMinor,
                    'down_payment_method' => $command->downPaymentMethod,
                    'special_agreement' => $command->specialAgreementNote,
                    'down_payment_deadline_at' => $deadline->toDateTimeString(),
                    'status' => $job->status(),
                ],
            );
        });
    }
}
