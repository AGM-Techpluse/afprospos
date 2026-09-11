<?php

declare(strict_types=1);

namespace Tests\Architecture;

use Tests\Architecture\Support\DependencyScanner;
use Tests\TestCase;

/**
 * CPNC §3.2: "A Command and its Handler MUST share the same root name.
 * InitiatePaymentCommand MUST be handled by InitiatePaymentHandler."
 */
class CommandHasMatchingHandlerArchTest extends TestCase
{
    public function test_every_application_command_has_a_matching_handler_in_the_same_module(): void
    {
        $violations = [];

        foreach (glob(base_path('domain/*/Application/Commands'), GLOB_ONLYDIR) as $commandsDir) {
            $module = basename(dirname(dirname($commandsDir)));
            $handlersDir = base_path("domain/{$module}/Application/Handlers");

            foreach (DependencyScanner::phpFiles($commandsDir) as $file) {
                $typeName = DependencyScanner::declaredTypeName($file);

                if ($typeName === null || ! str_ends_with($typeName, 'Command')) {
                    continue;
                }

                $commandClass = basename(str_replace('\\', '/', $typeName));
                $expectedHandler = substr($commandClass, 0, -strlen('Command')).'Handler';
                $expectedPath = "{$handlersDir}/{$expectedHandler}.php";

                if (! is_file($expectedPath)) {
                    $violations[] = sprintf(
                        '%s has no matching domain/%s/Application/Handlers/%s.php',
                        $typeName,
                        $module,
                        $expectedHandler,
                    );
                }
            }
        }

        $this->assertSame([], $violations, "Command/Handler pairing violations:\n".implode("\n", $violations));
    }
}
