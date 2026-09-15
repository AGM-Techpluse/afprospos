<?php

declare(strict_types=1);

namespace Domain\Collection\Application\Contracts;

use Domain\Collection\Application\Commands\CreateCollectionCaseCommand;

/**
 * The published cross-module contract — Repair (and later Warranty)
 * depends on this, never on Collection's Domain/Infrastructure
 * internals or Eloquent models directly (CPNC §4.2). Mirrors
 * PaymentInitiationService's role exactly.
 */
interface CollectionCaseCreator
{
    public function create(CreateCollectionCaseCommand $command): int;
}
