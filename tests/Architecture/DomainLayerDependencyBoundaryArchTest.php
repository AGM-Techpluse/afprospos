<?php

declare(strict_types=1);

namespace Tests\Architecture;

use Tests\Architecture\Support\DependencyScanner;
use Tests\TestCase;

/**
 * CPNC §4.1/§4.2: a Domain package may depend only on (a) itself,
 * (b) the Shared Kernel (Domain\Shared\Domain\*), or (c) another module's
 * published Application\Contracts\* interfaces. This is a whitelist, not
 * a blacklist of forbidden namespaces — it enforces §4.5's bullets on
 * Illuminate\Http, *Record/Eloquent, and vendor SDKs, plus any future
 * cross-module violation (e.g. "Payments/Domain depends on
 * Sales/Infrastructure"), all through one rule instead of enumerating
 * every forbidden pair by hand.
 */
class DomainLayerDependencyBoundaryArchTest extends TestCase
{
    public function test_domain_layer_only_depends_on_itself_the_shared_kernel_or_another_modules_contracts(): void
    {
        $violations = [];

        foreach (glob(base_path('domain/*/Domain'), GLOB_ONLYDIR) as $domainDir) {
            $module = basename(dirname($domainDir));

            foreach (DependencyScanner::phpFiles($domainDir) as $file) {
                foreach (DependencyScanner::importedNames($file) as $imported) {
                    if ($this->isAllowedDependency($imported, $module)) {
                        continue;
                    }

                    $violations[] = sprintf(
                        '%s imports %s, which is outside domain/%s/Domain, domain/Shared/Domain, and every module\'s Application/Contracts',
                        str_replace(base_path().DIRECTORY_SEPARATOR, '', $file),
                        $imported,
                        $module,
                    );
                }
            }
        }

        $this->assertSame([], $violations, "Domain layer boundary violations:\n".implode("\n", $violations));
    }

    private function isAllowedDependency(string $imported, string $ownModule): bool
    {
        if (! str_contains($imported, '\\')) {
            // No namespace separator: a PHP built-in/global (Exception, DateTimeImmutable, Stringable, ...).
            return true;
        }

        if (str_starts_with($imported, "Domain\\{$ownModule}\\Domain\\")) {
            return true;
        }

        if (str_starts_with($imported, 'Domain\\Shared\\Domain\\')) {
            return true;
        }

        if (str_starts_with($imported, 'Carbon\\')) {
            // CPNC's own canonical Domain Event example uses CarbonImmutable
            // directly (§3.3) — a pure value type, not a vendor SDK/service client.
            return true;
        }

        return (bool) preg_match('/^Domain\\\\[A-Za-z0-9]+\\\\Application\\\\Contracts\\\\/', $imported);
    }
}
