<?php

declare(strict_types=1);

namespace Domain\Collection\Infrastructure\Laravel;

use Domain\Collection\Application\Commands\CreateCollectionCaseCommand;
use Domain\Collection\Application\Contracts\CollectionCaseCreator;
use Domain\Collection\Application\Handlers\CreateCollectionCaseHandler;

/** The Contract's default implementation — delegates straight to the Handler, mirrors HandlerBackedPaymentInitiationService (CPNC §4.4). */
final class HandlerBackedCollectionCaseCreator implements CollectionCaseCreator
{
    public function __construct(private readonly CreateCollectionCaseHandler $handler) {}

    public function create(CreateCollectionCaseCommand $command): int
    {
        return $this->handler->handle($command);
    }
}
