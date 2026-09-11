<?php

declare(strict_types=1);

namespace Tests\Architecture;

use Tests\Architecture\Support\DependencyScanner;
use Tests\TestCase;

/**
 * CPNC §4.5: "Every interface in Application/Contracts is implemented by
 * at least one Infrastructure class" — the Dependency Inversion half of
 * Hexagonal Architecture (§4.4) is only real if every published contract
 * actually has an adapter behind it.
 */
class ContractHasImplementationArchTest extends TestCase
{
    public function test_every_application_contract_is_implemented_by_at_least_one_infrastructure_class(): void
    {
        $contractFiles = [];
        foreach (glob(base_path('domain/*/Application/Contracts'), GLOB_ONLYDIR) as $dir) {
            $contractFiles = [...$contractFiles, ...DependencyScanner::phpFiles($dir)];
        }

        $implementations = [];
        foreach (glob(base_path('domain/*/Infrastructure'), GLOB_ONLYDIR) as $dir) {
            foreach (DependencyScanner::phpFiles($dir) as $file) {
                foreach (DependencyScanner::implementedInterfaces($file) as $interface) {
                    $implementations[$interface][] = $file;
                }
            }
        }

        $violations = [];

        foreach ($contractFiles as $file) {
            $interfaceName = DependencyScanner::declaredTypeName($file);

            if ($interfaceName === null) {
                continue;
            }

            if (! isset($implementations[$interfaceName])) {
                $violations[] = "{$interfaceName} has no Infrastructure class implementing it";
            }
        }

        $this->assertSame([], $violations, "Unimplemented contracts:\n".implode("\n", $violations));
    }
}
