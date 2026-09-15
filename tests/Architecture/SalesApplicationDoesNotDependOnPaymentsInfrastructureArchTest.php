<?php

declare(strict_types=1);

namespace Tests\Architecture;

use Tests\Architecture\Support\DependencyScanner;
use Tests\TestCase;

/**
 * CPNC §4.2: Sales' Application layer now depends on Payments (Phase 5's
 * PaymentInitiationService) for the first time — this is the one place a
 * cross-module Application-layer dependency exists in the codebase today,
 * so it gets its own targeted guard: Sales may depend on
 * Domain\Payments\Application\Contracts\* and Domain\Payments\Application\Commands\*
 * (the Contract's own method signatures use Command classes as their
 * parameter type, same as InventoryReservationService/ReserveInventoryCommand
 * — Sales already imports Domain\Inventory\Application\Commands\* directly
 * for exactly this reason) — never Domain\Payments\Infrastructure\*
 * (gateways, the Eloquent record, the service provider) directly.
 */
class SalesApplicationDoesNotDependOnPaymentsInfrastructureArchTest extends TestCase
{
    private const ALLOWED_PREFIXES = [
        'Domain\\Payments\\Application\\Contracts\\',
        'Domain\\Payments\\Application\\Commands\\',
    ];

    public function test_sales_application_only_depends_on_payments_application_contracts_and_commands(): void
    {
        $violations = [];

        foreach (DependencyScanner::phpFiles(base_path('domain/Sales/Application')) as $file) {
            foreach (DependencyScanner::importedNames($file) as $imported) {
                if (! str_starts_with($imported, 'Domain\\Payments\\')) {
                    continue;
                }

                if ($this->isAllowed($imported)) {
                    continue;
                }

                $violations[] = sprintf(
                    '%s imports %s — Sales may only depend on Domain\\Payments\\Application\\{Contracts,Commands}\\*',
                    str_replace(base_path().DIRECTORY_SEPARATOR, '', $file),
                    $imported,
                );
            }
        }

        $this->assertSame([], $violations, "Sales -> Payments boundary violations:\n".implode("\n", $violations));
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
