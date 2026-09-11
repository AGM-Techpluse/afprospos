<?php

declare(strict_types=1);

namespace Domain\Inventory\Application\Handlers;

use Domain\Inventory\Application\Commands\CreateProductCommand;
use Domain\Inventory\Application\Commands\ImportProductsCommand;
use Domain\Inventory\Application\DTOs\ProductImportRowResult;
use Domain\Inventory\Domain\Exceptions\DuplicateSkuCode;
use Domain\Inventory\Domain\Exceptions\ImeiAlreadyExists;
use Domain\Inventory\Domain\Repositories\SkuRepository;
use Throwable;

/**
 * BRD INV-01: each row gets its own transaction (via CreateProductHandler,
 * reused as-is) so one bad row can never roll back the whole batch.
 */
final class ImportProductsHandler
{
    public function __construct(
        private readonly CreateProductHandler $createProduct,
        private readonly SkuRepository $skus,
    ) {}

    /** @return ProductImportRowResult[] */
    public function handle(ImportProductsCommand $command): array
    {
        $results = [];

        foreach ($command->rows as $row) {
            try {
                $skuId = $this->createProduct->handle(new CreateProductCommand(
                    brand: $row->brand,
                    model: $row->model,
                    category: $row->category,
                    attributes: [],
                    isSerialized: $row->imei !== null,
                    costPriceMinor: $row->costPriceMinor,
                    markupPercent: $row->markupPercent,
                    sellingPriceMinorOverride: null,
                    lowStockThreshold: $row->lowStockThreshold,
                    shopCode: $command->shopCode,
                    shopId: $command->shopId,
                    condition: $row->condition,
                    initialQuantity: $row->quantity,
                    initialImeis: $row->imei !== null ? [$row->imei] : null,
                    createdByStaffId: $command->importedByStaffId,
                ));

                $results[] = new ProductImportRowResult($row->rowNumber, true, $this->skus->get($skuId)->skuCode(), null);
            } catch (DuplicateSkuCode|ImeiAlreadyExists $exception) {
                $results[] = new ProductImportRowResult($row->rowNumber, false, null, $exception->getMessage());
            } catch (Throwable $exception) {
                $results[] = new ProductImportRowResult($row->rowNumber, false, null, $exception->getMessage());
            }
        }

        return $results;
    }
}
