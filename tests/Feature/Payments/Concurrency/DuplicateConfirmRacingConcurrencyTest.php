<?php

declare(strict_types=1);

namespace Tests\Feature\Payments\Concurrency;

use Domain\Payments\Infrastructure\Persistence\Eloquent\PaymentTransactionRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\Support\UsesConcurrencyDatabase;
use Tests\TestCase;

/**
 * DBDD §16.2: "the application must not change an already-confirmed
 * payment to a different terminal state simply because a duplicate ...
 * callback arrived." Two real OS processes confirm the same payment
 * transaction simultaneously — the SELECT ... FOR UPDATE on
 * payments_transactions is what makes exactly one of them the "real"
 * confirm and the other an idempotent no-op, never both racing past a
 * pre-lock read of the same 'payment_pending_confirmation' status.
 */
class DuplicateConfirmRacingConcurrencyTest extends TestCase
{
    use UsesConcurrencyDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpConcurrencyDatabase();
    }

    protected function tearDown(): void
    {
        $this->tearDownConcurrencyDatabase(['audit_logs', 'payments_transactions', 'staff']);
        parent::tearDown();
    }

    public function test_only_one_of_two_concurrent_confirms_becomes_the_real_confirmation(): void
    {
        $staffA = StaffRecord::factory()->create();
        $staffB = StaffRecord::factory()->create();

        $transaction = PaymentTransactionRecord::factory()->pendingConfirmation()->create();

        $processA = $this->buildProcess($transaction->id, $staffA->id, holdMs: 400);
        $processB = $this->buildProcess($transaction->id, $staffB->id, holdMs: 0);

        $processA->start();
        usleep(50_000);
        $processB->start();

        $processA->wait();
        $processB->wait();

        $resultA = json_decode($processA->getOutput(), true);
        $resultB = json_decode($processB->getOutput(), true);

        // ConfirmPaymentHandler swallows the duplicate case as an idempotent
        // no-op rather than throwing, so BOTH processes report success —
        // the invariant under test is the database state, not the exit status.
        $this->assertTrue($resultA['success'] ?? false, 'Process A: '.$processA->getOutput().' '.$processA->getErrorOutput());
        $this->assertTrue($resultB['success'] ?? false, 'Process B: '.$processB->getOutput().' '.$processB->getErrorOutput());

        $fresh = PaymentTransactionRecord::query()->findOrFail($transaction->id);
        $this->assertSame('confirmed', $fresh->status);
        $this->assertContains($fresh->confirmed_by_staff_id, [$staffA->id, $staffB->id]);

        $confirmedCount = DB::connection('mysql')->table('audit_logs')
            ->where('subject_type', 'payments_transaction')
            ->where('subject_id', $transaction->id)
            ->where('event_type', 'PaymentConfirmed')
            ->count();
        $duplicateIgnoredCount = DB::connection('mysql')->table('audit_logs')
            ->where('subject_type', 'payments_transaction')
            ->where('subject_id', $transaction->id)
            ->where('event_type', 'PaymentConfirmDuplicateIgnored')
            ->count();

        $this->assertSame(1, $confirmedCount, 'exactly one process must have performed the real confirmation.');
        $this->assertSame(1, $duplicateIgnoredCount, 'exactly one process must have hit the idempotent duplicate path.');
    }

    private function buildProcess(int $transactionId, int $staffId, int $holdMs): Process
    {
        return new Process([
            PHP_BINARY,
            __DIR__.'/Support/attempt_confirm_payment.php',
            "--transaction={$transactionId}",
            "--staff={$staffId}",
            "--hold-ms={$holdMs}",
        ], timeout: 15);
    }
}
