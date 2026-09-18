<?php

declare(strict_types=1);

namespace Domain\Warranty\Application\Commands;

/** WAR-BR-12's administrative-override path — approving a return that was denied (typically for being outside the return window). */
final readonly class OverrideApproveReturnRequestCommand
{
    public function __construct(
        public int $returnRequestId,
        public int $overriddenByStaffId,
    ) {}
}
