<?php

declare(strict_types=1);

namespace Tests\Architecture;

use Tests\Architecture\Support\DependencyScanner;
use Tests\TestCase;

/**
 * CPNC §4.2: Warranty's Application layer may depend on other modules'
 * published Application\Contracts\* and Application\Commands\* only
 * (a Contract's own method signatures use Command classes as their
 * parameter type — same reasoning as
 * SalesApplicationDoesNotDependOnPaymentsInfrastructureArchTest), never
 * their Domain/Infrastructure internals.
 */
class WarrantyApplicationDependencyBoundaryArchTest extends TestCase
{
    private const WATCHED_MODULES = ['Sales', 'Repair', 'Payments', 'Inventory'];

    private const ALLOWED_SUFFIXES = ['Application\\Contracts\\', 'Application\\Commands\\'];

    public function test_warranty_application_only_depends_on_other_modules_published_contracts_and_commands(): void
    {
        $violations = [];

        foreach (DependencyScanner::phpFiles(base_path('domain/Warranty/Application')) as $file) {
            foreach (DependencyScanner::importedNames($file) as $imported) {
                foreach (self::WATCHED_MODULES as $module) {
                    if (! str_starts_with($imported, "Domain\\{$module}\\")) {
                        continue;
                    }

                    if ($this->isAllowed($imported, $module)) {
                        continue 2;
                    }

                    $violations[] = sprintf(
                        '%s imports %s — Warranty may only depend on Domain\\%s\\Application\\{Contracts,Commands}\\*',
                        str_replace(base_path().DIRECTORY_SEPARATOR, '', $file),
                        $imported,
                        $module,
                    );
                }
            }
        }

        $this->assertSame([], $violations, "Warranty -> other-module boundary violations:\n".implode("\n", $violations));
    }

    private function isAllowed(string $imported, string $module): bool
    {
        foreach (self::ALLOWED_SUFFIXES as $suffix) {
            if (str_starts_with($imported, "Domain\\{$module}\\{$suffix}")) {
                return true;
            }
        }

        return false;
    }
}
