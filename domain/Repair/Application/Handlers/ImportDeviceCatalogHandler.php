<?php

declare(strict_types=1);

namespace Domain\Repair\Application\Handlers;

use Domain\Repair\Application\Commands\CreateDeviceBrandCommand;
use Domain\Repair\Application\Commands\CreateDeviceModelCommand;
use Domain\Repair\Application\Commands\CreateDeviceProblemTagCommand;
use Domain\Repair\Application\Commands\CreateDeviceTypeCommand;
use Domain\Repair\Application\Commands\ImportDeviceCatalogCommand;
use Domain\Repair\Application\Commands\UpdateDeviceProblemTagCommand;
use Domain\Repair\Application\DTOs\DeviceCatalogImportRow;
use Domain\Repair\Application\DTOs\DeviceCatalogImportRowResult;
use Domain\Repair\Domain\Repositories\DeviceBrandRepository;
use Domain\Repair\Domain\Repositories\DeviceModelRepository;
use Domain\Repair\Domain\Repositories\DeviceProblemTagRepository;
use Domain\Repair\Domain\Repositories\DeviceTypeRepository;
use Domain\Repair\Domain\ValueObjects\DeviceBrandId;
use Domain\Repair\Domain\ValueObjects\DeviceModelId;
use Domain\Repair\Domain\ValueObjects\DeviceTypeId;
use Throwable;

/**
 * Each row gets its own find-or-create chain (one bad row can never roll
 * back the whole batch — mirrors ImportProductsHandler's per-row isolation
 * exactly). Unlike product import, every level here is idempotent: a
 * repeat import of the same file reuses existing types/brands/models/tags
 * rather than erroring or duplicating, so the same CSV can be re-run
 * safely (e.g. after fixing a typo in a later row).
 */
final class ImportDeviceCatalogHandler
{
    public function __construct(
        private readonly DeviceTypeRepository $deviceTypes,
        private readonly DeviceBrandRepository $deviceBrands,
        private readonly DeviceModelRepository $deviceModels,
        private readonly DeviceProblemTagRepository $problemTags,
        private readonly CreateDeviceTypeHandler $createDeviceType,
        private readonly CreateDeviceBrandHandler $createDeviceBrand,
        private readonly CreateDeviceModelHandler $createDeviceModel,
        private readonly CreateDeviceProblemTagHandler $createProblemTag,
        private readonly UpdateDeviceProblemTagHandler $updateProblemTag,
    ) {}

    /** @return DeviceCatalogImportRowResult[] */
    public function handle(ImportDeviceCatalogCommand $command): array
    {
        $results = [];

        foreach ($command->rows as $row) {
            try {
                $results[] = $this->importRow($row, $command->importedByStaffId);
            } catch (Throwable $exception) {
                $results[] = new DeviceCatalogImportRowResult($row->rowNumber, false, null, $exception->getMessage());
            }
        }

        return $results;
    }

    private function importRow(DeviceCatalogImportRow $row, int $staffId): DeviceCatalogImportRowResult
    {
        $typeId = $this->findOrCreateType($row->deviceTypeLabel, $row->icon, $staffId);
        $brandId = $this->findOrCreateBrand($typeId, $row->brandName, $staffId);

        $summary = "{$row->deviceTypeLabel} → {$row->brandName}";
        $modelId = null;

        if ($row->modelName !== null) {
            $modelId = $this->createDeviceModel->handle(new CreateDeviceModelCommand(
                deviceBrandId: $brandId->value,
                name: $row->modelName,
                sortOrder: 0,
                createdByStaffId: $staffId,
            ));
            $summary .= " → {$row->modelName}";
        }

        if ($row->faultLabel !== null) {
            $this->findOrCreateOrUpdateFault($typeId, $modelId !== null ? new DeviceModelId($modelId) : null, $row->faultLabel, $row->commissionAmountMinor, $staffId);
            $summary .= " ({$row->faultLabel})";
        }

        return new DeviceCatalogImportRowResult($row->rowNumber, true, $summary, null);
    }

    private function findOrCreateType(string $label, string $icon, int $staffId): DeviceTypeId
    {
        $existing = $this->deviceTypes->findByLabel($label);

        if ($existing !== null) {
            return $existing->id();
        }

        return new DeviceTypeId($this->createDeviceType->handle(new CreateDeviceTypeCommand(
            label: $label,
            icon: $icon,
            sortOrder: 0,
            createdByStaffId: $staffId,
        )));
    }

    private function findOrCreateBrand(DeviceTypeId $typeId, string $name, int $staffId): DeviceBrandId
    {
        $existing = $this->deviceBrands->findByTypeAndName($typeId, $name);

        if ($existing !== null) {
            return $existing->id();
        }

        return new DeviceBrandId($this->createDeviceBrand->handle(new CreateDeviceBrandCommand(
            deviceTypeId: $typeId->value,
            name: $name,
            sortOrder: 0,
            createdByStaffId: $staffId,
        )));
    }

    private function findOrCreateOrUpdateFault(DeviceTypeId $typeId, ?DeviceModelId $modelId, string $label, ?int $commissionAmountMinor, int $staffId): void
    {
        $existing = $this->problemTags->findByScopeAndLabel($typeId, $modelId, $label);

        if ($existing === null) {
            $this->createProblemTag->handle(new CreateDeviceProblemTagCommand(
                deviceTypeId: $modelId === null ? $typeId->value : null,
                deviceModelId: $modelId?->value,
                label: $label,
                sortOrder: 0,
                createdByStaffId: $staffId,
                commissionAmountMinor: $commissionAmountMinor,
            ));

            return;
        }

        if ($commissionAmountMinor !== null) {
            $this->updateProblemTag->handle(new UpdateDeviceProblemTagCommand(
                deviceProblemTagId: $existing->id()->value,
                label: $existing->label(),
                sortOrder: $existing->sortOrder(),
                updatedByStaffId: $staffId,
                commissionAmountMinor: $commissionAmountMinor,
            ));
        }
    }
}
