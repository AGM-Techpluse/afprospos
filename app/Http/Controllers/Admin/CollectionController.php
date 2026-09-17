<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\FlashesToast;
use App\Http\Requests\Admin\Collection\ExtendDeadlineRequest;
use App\Http\Requests\Admin\Collection\RecordNotifiedRequest;
use App\Http\Requests\Admin\Collection\RecordOverrideRequest;
use Carbon\CarbonImmutable;
use Domain\Collection\Application\Commands\ExtendCollectionDeadlineCommand;
use Domain\Collection\Application\Commands\RecordCollectionNotifiedCommand;
use Domain\Collection\Application\Commands\RecordCollectionOverrideCommand;
use Domain\Collection\Application\Commands\ReleaseRepairDeviceCommand;
use Domain\Collection\Application\Handlers\ExtendCollectionDeadlineHandler;
use Domain\Collection\Application\Handlers\RecordCollectionNotifiedHandler;
use Domain\Collection\Application\Handlers\RecordCollectionOverrideHandler;
use Domain\Collection\Application\Handlers\ReleaseRepairDeviceHandler;
use Domain\Collection\Application\Queries\CollectionCaseDetailQuery;
use Domain\Collection\Application\Queries\CollectionQueueQuery;
use Domain\Collection\Domain\Exceptions\ReleaseBlockedByOutstandingBalance;
use Domain\Shared\Application\DTOs\ActorContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class CollectionController
{
    use FlashesToast;

    public function __construct(
        private readonly CollectionQueueQuery $queue,
        private readonly CollectionCaseDetailQuery $detail,
        private readonly RecordCollectionNotifiedHandler $recordNotified,
        private readonly ExtendCollectionDeadlineHandler $extendDeadline,
        private readonly RecordCollectionOverrideHandler $recordOverride,
        private readonly ReleaseRepairDeviceHandler $releaseDevice,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Admin/Collection/Index', [
            'cases' => $this->queue->paginate(
                status: $request->string('status')->toString() ?: null,
                shopId: $request->integer('shop_id') ?: null,
                page: $request->integer('page', 1),
            ),
            'filters' => [
                'status' => $request->string('status')->toString(),
                'shop_id' => $request->integer('shop_id') ?: null,
            ],
        ]);
    }

    public function show(int $collectionCase): Response
    {
        $data = $this->detail->find($collectionCase);

        abort_if($data === null, 404);

        return Inertia::render('Admin/Collection/Show', ['collectionCase' => $data]);
    }

    public function notify(RecordNotifiedRequest $request, int $collectionCase): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->recordNotified->handle(new RecordCollectionNotifiedCommand(
            collectionCaseId: $collectionCase,
            notifiedByStaffId: $actor->staffId->value,
            note: $request->string('note')->toString() ?: null,
        ));

        $this->flashSuccess('Customer notified');

        return redirect()->route('admin.collection.show', ['collectionCase' => $collectionCase]);
    }

    public function extend(ExtendDeadlineRequest $request, int $collectionCase): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->extendDeadline->handle(new ExtendCollectionDeadlineCommand(
            collectionCaseId: $collectionCase,
            newDeadlineAt: CarbonImmutable::parse($request->string('new_deadline_at')->toString()),
            newAbandonmentThresholdAt: null,
            extendedByStaffId: $actor->staffId->value,
            reason: $request->string('reason')->toString(),
        ));

        $this->flashSuccess('Collection deadline extended');

        return redirect()->route('admin.collection.show', ['collectionCase' => $collectionCase]);
    }

    public function override(RecordOverrideRequest $request, int $collectionCase): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->recordOverride->handle(new RecordCollectionOverrideCommand(
            collectionCaseId: $collectionCase,
            overriddenByStaffId: $actor->staffId->value,
            reason: $request->string('reason')->toString(),
        ));

        $this->flashWarning('Override recorded');

        return redirect()->route('admin.collection.show', ['collectionCase' => $collectionCase]);
    }

    public function release(int $collectionCase): RedirectResponse
    {
        $actor = app(ActorContext::class);

        try {
            $this->releaseDevice->handle(new ReleaseRepairDeviceCommand($collectionCase, $actor->staffId->value));
        } catch (ReleaseBlockedByOutstandingBalance $exception) {
            throw ValidationException::withMessages(['status' => $exception->getMessage()]);
        }

        $this->flashSuccess('Device released');

        return redirect()->route('admin.collection.show', ['collectionCase' => $collectionCase]);
    }
}
