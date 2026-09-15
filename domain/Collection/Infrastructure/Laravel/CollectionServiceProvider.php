<?php

declare(strict_types=1);

namespace Domain\Collection\Infrastructure\Laravel;

use Domain\Collection\Application\Contracts\CollectionCaseCreator;
use Domain\Collection\Application\Contracts\CollectionCaseLookup;
use Domain\Collection\Domain\Repositories\CollectionCaseEventRepository;
use Domain\Collection\Domain\Repositories\CollectionCaseRepository;
use Domain\Collection\Infrastructure\Persistence\Repositories\EloquentCollectionCaseEventRepository;
use Domain\Collection\Infrastructure\Persistence\Repositories\EloquentCollectionCaseRepository;
use Illuminate\Support\ServiceProvider;

final class CollectionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CollectionCaseRepository::class, EloquentCollectionCaseRepository::class);
        $this->app->bind(CollectionCaseEventRepository::class, EloquentCollectionCaseEventRepository::class);
        $this->app->bind(CollectionCaseCreator::class, HandlerBackedCollectionCaseCreator::class);
        $this->app->bind(CollectionCaseLookup::class, EloquentCollectionCaseLookup::class);
    }
}
