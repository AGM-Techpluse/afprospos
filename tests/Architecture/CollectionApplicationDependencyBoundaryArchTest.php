<?php

declare(strict_types=1);

namespace Tests\Architecture;

use Tests\Architecture\Support\DependencyScanner;
use Tests\TestCase;

/** CPNC §4.2: Collection's Application layer may depend on Repair's published Application\Contracts\* only, never Repair's Domain/Infrastructure internals. */
class CollectionApplicationDependencyBoundaryArchTest extends TestCase
{
    public function test_collection_application_only_depends_on_repair_application_contracts(): void
    {
        $violations = [];

        foreach (DependencyScanner::phpFiles(base_path('domain/Collection/Application')) as $file) {
            foreach (DependencyScanner::importedNames($file) as $imported) {
                if (! str_starts_with($imported, 'Domain\\Repair\\')) {
                    continue;
                }

                if (str_starts_with($imported, 'Domain\\Repair\\Application\\Contracts\\')) {
                    continue;
                }

                $violations[] = sprintf(
                    '%s imports %s — Collection may only depend on Domain\\Repair\\Application\\Contracts\\*',
                    str_replace(base_path().DIRECTORY_SEPARATOR, '', $file),
                    $imported,
                );
            }
        }

        $this->assertSame([], $violations, "Collection -> Repair boundary violations:\n".implode("\n", $violations));
    }
}
