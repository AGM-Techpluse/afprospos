<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use Domain\Repair\Application\Commands\AddRevealedFaultCommand;
use Domain\Repair\Application\Commands\CreateDeviceProblemTagCommand;
use Domain\Repair\Domain\Repositories\DeviceBrandRepository;
use Domain\Repair\Domain\Repositories\DeviceModelRepository;
use Domain\Repair\Domain\Repositories\DeviceProblemTagRepository;
use Domain\Repair\Domain\ValueObjects\DeviceModelId;
use Domain\Repair\Infrastructure\Persistence\Eloquent\DiagnosisCaseRecord;

/**
 * Resolves a device_problem_tag_id for a technician-typed "unique
 * revealed fault" label — reusing an existing tag of that exact label at
 * the diagnosing device's own type scope if one exists (mirrors
 * ImportDeviceCatalogHandler's own idempotency), else creating a new
 * type-level one, so it joins the catalog and is immediately findable
 * from a future diagnosis case's search too. Returns the resolved id/
 * label for the caller to add to its own in-progress revealed-faults
 * list — the actual DiagnosisCaseFaultRecord is written later by
 * DiagnosisCasesController::store()/saveResult()'s existing bulk-replace
 * save, unchanged.
 */
final class AddRevealedFaultHandler
{
    public function __construct(
        private readonly DeviceProblemTagRepository $problemTags,
        private readonly DeviceModelRepository $deviceModels,
        private readonly DeviceBrandRepository $deviceBrands,
        private readonly CreateDeviceProblemTagHandler $createProblemTag,
    ) {}

    /** @return array{id: int, label: string}|null null only for a legacy case with no device model — no type to anchor a new catalog entry to. */
    public function handle(AddRevealedFaultCommand $command): ?array
    {
        $case = DiagnosisCaseRecord::query()->findOrFail($command->diagnosisCaseId);

        if ($case->device_model_id === null) {
            return null;
        }

        $model = $this->deviceModels->get(new DeviceModelId($case->device_model_id));
        $brand = $this->deviceBrands->get($model->deviceBrandId());
        $deviceTypeId = $brand->deviceTypeId();
        $label = $command->label;

        $existing = $this->problemTags->findByScopeAndLabel($deviceTypeId, null, $label);

        if ($existing !== null) {
            return ['id' => $existing->id()->value, 'label' => $existing->label()];
        }

        $tagId = $this->createProblemTag->handle(new CreateDeviceProblemTagCommand(
            deviceTypeId: $deviceTypeId->value,
            deviceModelId: null,
            label: $label,
            sortOrder: 0,
            createdByStaffId: $command->addedByStaffId,
        ));

        return ['id' => $tagId, 'label' => $label];
    }
}
