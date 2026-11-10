<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use App\Support\Transactions\Atomic;
use Domain\Audit\Application\Contracts\AuditWriter;
use Domain\Repair\Application\Commands\BulkAddDeviceProblemTagsCommand;
use Domain\Repair\Domain\Entities\DeviceProblemTag;
use Domain\Repair\Domain\Repositories\DeviceBrandRepository;
use Domain\Repair\Domain\Repositories\DeviceModelRepository;
use Domain\Repair\Domain\Repositories\DeviceProblemTagRepository;
use Domain\Repair\Domain\ValueObjects\DeviceModelId;
use Domain\Shared\Domain\ValueObjects\StaffId;

final class BulkAddDeviceProblemTagsHandler
{
    public function __construct(
        private readonly DeviceProblemTagRepository $problemTags,
        private readonly DeviceModelRepository $deviceModels,
        private readonly DeviceBrandRepository $deviceBrands,
        private readonly AuditWriter $audit,
        private readonly Atomic $atomic,
    ) {}

    /** @return array{created: array<int, array{id:int, label:string}>, skipped: array<int, string>} */
    public function handle(BulkAddDeviceProblemTagsCommand $command): array
    {
        return $this->atomic->run(function () use ($command): array {
            $deviceModelId = new DeviceModelId($command->deviceModelId);
            $model = $this->deviceModels->get($deviceModelId);
            $brand = $this->deviceBrands->get($model->deviceBrandId());
            $deviceTypeId = $brand->deviceTypeId();

            $created = [];
            $skipped = [];
            $sortOrder = $command->startingSortOrder;

            foreach ($command->labels as $label) {
                $label = trim($label);

                if ($label === '' || $this->problemTags->findByScopeAndLabel($deviceTypeId, $deviceModelId, $label) !== null) {
                    $skipped[] = $label;

                    continue;
                }

                $tag = DeviceProblemTag::create($deviceTypeId, $deviceModelId, $label, $sortOrder);
                $id = $this->problemTags->save($tag);
                $created[] = ['id' => $id->value, 'label' => $label];
                $sortOrder++;
            }

            if ($created !== []) {
                $this->audit->record(
                    module: 'Repair',
                    eventType: 'DeviceProblemTagsBulkAdded',
                    actorStaffId: new StaffId($command->createdByStaffId),
                    actorRoleSnapshot: null,
                    subjectType: 'device_model',
                    subjectId: $deviceModelId->value,
                    beforeState: null,
                    afterState: ['device_type_id' => $deviceTypeId->value, 'tags' => $created],
                );
            }

            return ['created' => $created, 'skipped' => $skipped];
        });
    }
}
