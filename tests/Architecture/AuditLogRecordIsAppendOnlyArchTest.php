<?php

declare(strict_types=1);

namespace Tests\Architecture;

use Tests\Architecture\Support\DependencyScanner;
use Tests\TestCase;

/**
 * CPNC §4.5: "Audit-table Eloquent models expose no update()/delete()
 * path" — DBDD §8's Immutability Model requires corrections to be new
 * audit rows, never edits to an existing one. AuditLogRecord itself
 * (domain/Audit/Infrastructure/Persistence/Eloquent/AuditLogRecord.php)
 * documents this as a codebase-wide discipline rather than a runtime
 * guard, so this test is what actually enforces it.
 */
class AuditLogRecordIsAppendOnlyArchTest extends TestCase
{
    private const MUTATING_CALLS = ['->update(', '->delete(', '->forceDelete(', '::destroy('];

    public function test_no_file_mutates_or_deletes_an_audit_log_record(): void
    {
        $recordFile = base_path('domain/Audit/Infrastructure/Persistence/Eloquent/AuditLogRecord.php');
        $violations = [];

        $searchRoots = [base_path('app'), base_path('domain')];

        foreach ($searchRoots as $root) {
            foreach (DependencyScanner::phpFiles($root) as $file) {
                if ($file === $recordFile) {
                    continue; // the model's own definition, not a call site
                }

                $contents = file_get_contents($file) ?: '';

                if (! str_contains($contents, 'AuditLogRecord')) {
                    continue;
                }

                foreach (self::MUTATING_CALLS as $call) {
                    if (str_contains($contents, $call)) {
                        $violations[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file)." contains `{$call}` alongside an AuditLogRecord reference";
                    }
                }
            }
        }

        $this->assertSame([], $violations, "Audit log mutation violations:\n".implode("\n", $violations));
    }
}
