<?php

declare(strict_types=1);

namespace Domain\Collection\Application\Handlers;

use App\Support\Transactions\Atomic;
use Carbon\CarbonImmutable;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Collection\Application\Commands\CreateCollectionCaseCommand;
use Domain\Collection\Domain\Entities\CollectionCase;
use Domain\Collection\Domain\Entities\CollectionCaseEvent;
use Domain\Collection\Domain\Events\CollectionCaseCreated;
use Domain\Collection\Domain\Repositories\CollectionCaseEventRepository;
use Domain\Collection\Domain\Repositories\CollectionCaseRepository;
use Illuminate\Support\Facades\Event;

final class CreateCollectionCaseHandler
{
    public function __construct(
        private readonly CollectionCaseRepository $cases,
        private readonly CollectionCaseEventRepository $events,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    public function handle(CreateCollectionCaseCommand $command): int
    {
        return $this->atomic->run(function () use ($command): int {
            $now = CarbonImmutable::now();
            $deadline = $now->addDays($command->deadlineDays);
            $abandonmentDays = (int) config('afprospos.collection.abandonment_threshold_days');
            $abandonmentThreshold = $abandonmentDays > 0 ? $deadline->addDays($abandonmentDays) : null;

            $snapshot = [
                'minor_per_day' => (int) config('afprospos.collection.storage_fee_minor_per_day'),
                'grace_days' => (int) config('afprospos.collection.grace_days'),
            ];

            $case = CollectionCase::open(
                $command->sourceType,
                $command->sourceId,
                $command->shopId,
                $command->originatingShopId,
                $command->context,
                $deadline,
                $abandonmentThreshold,
                $snapshot,
            );

            $id = $this->cases->save($case);

            $this->events->save(CollectionCaseEvent::record(
                $id,
                'deadline_set',
                null,
                ['collection_deadline_at' => $deadline->toDateTimeString()],
            ));

            $this->audit->record(
                module: 'Collection',
                eventType: 'CollectionCaseCreated',
                actorStaffId: null,
                actorRoleSnapshot: null,
                subjectType: 'collection_case',
                subjectId: $id->value,
                beforeState: null,
                afterState: [
                    'source_type' => $command->sourceType,
                    'source_id' => $command->sourceId,
                    'context' => $command->context,
                    'collection_deadline_at' => $deadline->toDateTimeString(),
                ],
            );

            Event::dispatch(new CollectionCaseCreated($id->value, $command->sourceType, $command->sourceId, $now));

            return $id->value;
        });
    }
}
