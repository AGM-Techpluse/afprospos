<?php

declare(strict_types=1);

namespace Tests\Architecture;

use Tests\Architecture\Support\DependencyScanner;
use Tests\TestCase;

/**
 * CPNC §4.2: Repair's Application layer depends on Inventory, Payments,
 * and Collection for the first time — this guard covers all three in one
 * place, same shape as SalesApplicationDoesNotDependOnPaymentsInfrastructureArchTest.
 * Allowed: each target module's own Application\{Contracts,Commands}\*
 * (the Contract's own method signatures use Command classes as their
 * parameter type — Sales already established this exact pattern with
 * Inventory). Never allowed: any target module's Infrastructure\*.
 */
class RepairApplicationDependencyBoundaryArchTest extends TestCase
{
    private const ALLOWED_PREFIXES = [
        'Domain\\Inventory\\Application\\Contracts\\',
        'Domain\\Inventory\\Application\\Commands\\',
        'Domain\\Payments\\Application\\Contracts\\',
        'Domain\\Payments\\Application\\Commands\\',
        'Domain\\Collection\\Application\\Contracts\\',
        'Domain\\Collection\\Application\\Commands\\',
    ];

    private const WATCHED_MODULE_PREFIXES = [
        'Domain\\Inventory\\',
        'Domain\\Payments\\',
        'Domain\\Collection\\',
    ];

    public function test_repair_application_only_depends_on_inventory_payments_and_collection_contracts_and_commands(): void
    {
        $violations = [];

        foreach (DependencyScanner::phpFiles(base_path('domain/Repair/Application')) as $file) {
            foreach (DependencyScanner::importedNames($file) as $imported) {
                if (! $this->isWatched($imported) || $this->isAllowed($imported)) {
                    continue;
                }

                $violations[] = sprintf(
                    '%s imports %s — Repair may only depend on Inventory/Payments/Collection Application\\{Contracts,Commands}\\*',
                    str_replace(base_path().DIRECTORY_SEPARATOR, '', $file),
                    $imported,
                );
            }
        }

        $this->assertSame([], $violations, "Repair Application boundary violations:\n".implode("\n", $violations));
    }

    private function isWatched(string $imported): bool
    {
        foreach (self::WATCHED_MODULE_PREFIXES as $prefix) {
            if (str_starts_with($imported, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function isAllowed(string $imported): bool
    {
        foreach (self::ALLOWED_PREFIXES as $prefix) {
            if (str_starts_with($imported, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
