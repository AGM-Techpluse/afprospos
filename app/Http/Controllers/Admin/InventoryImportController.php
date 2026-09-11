<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\ImportProductsRequest;
use Domain\Inventory\Application\Commands\ImportProductsCommand;
use Domain\Inventory\Application\DTOs\ProductImportRow;
use Domain\Inventory\Application\Handlers\ImportProductsHandler;
use Domain\Shared\Application\DTOs\ActorContext;
use Domain\Shop\Application\Queries\ShopDirectoryQuery;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Inertia\Response;

/**
 * BRD INV-01: fixed, documented CSV columns —
 * brand,model,category,condition,cost_price_minor,markup_percent,quantity,imei,low_stock_threshold
 * (quantity XOR imei per row, matching serialized/non-serialized SKUs).
 * Parsing the raw file is a Controller-level concern (CPNC §2.3); the
 * Command carries already-parsed rows, and one bad row never rolls
 * back the batch (ImportProductsHandler).
 */
final class InventoryImportController
{
    private const EXPECTED_HEADER = [
        'brand', 'model', 'category', 'condition',
        'cost_price_minor', 'markup_percent', 'quantity', 'imei', 'low_stock_threshold',
    ];

    public function __construct(
        private readonly ShopDirectoryQuery $shops,
        private readonly ImportProductsHandler $importProducts,
    ) {}

    public function create(): Response
    {
        return Inertia::render('Admin/Inventory/Import/Create', [
            'shops' => $this->shops->all(),
            'expectedHeader' => self::EXPECTED_HEADER,
        ]);
    }

    public function store(ImportProductsRequest $request): Response
    {
        $actor = app(ActorContext::class);
        $shop = $this->shops->find($request->integer('shop_id'));

        abort_if($shop === null, 404);

        $rows = $this->parseCsv($request->file('file'));

        $results = $this->importProducts->handle(new ImportProductsCommand(
            rows: $rows,
            shopCode: $shop['sku_prefix_code'],
            shopId: $shop['id'],
            importedByStaffId: $actor->staffId->value,
        ));

        return Inertia::render('Admin/Inventory/Import/Results', [
            'results' => array_map(static fn ($result): array => [
                'row_number' => $result->rowNumber,
                'succeeded' => $result->succeeded,
                'sku_code' => $result->skuCode,
                'error_message' => $result->errorMessage,
            ], $results),
        ]);
    }

    /** @return ProductImportRow[] */
    private function parseCsv(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'rb');
        $header = fgetcsv($handle);
        $rows = [];
        $rowNumber = 1;

        while (($csvRow = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if ($header === false || count($csvRow) < count(self::EXPECTED_HEADER)) {
                continue;
            }

            $row = array_combine($header, $csvRow);

            $rows[] = new ProductImportRow(
                rowNumber: $rowNumber,
                brand: trim((string) ($row['brand'] ?? '')),
                model: trim((string) ($row['model'] ?? '')),
                category: trim((string) ($row['category'] ?? '')),
                condition: trim((string) ($row['condition'] ?? 'new')),
                costPriceMinor: (int) ($row['cost_price_minor'] ?? 0),
                markupPercent: (float) ($row['markup_percent'] ?? 0),
                quantity: isset($row['quantity']) && $row['quantity'] !== '' ? (int) $row['quantity'] : null,
                imei: ($row['imei'] ?? '') !== '' ? trim((string) $row['imei']) : null,
                lowStockThreshold: ($row['low_stock_threshold'] ?? '') !== '' ? (int) $row['low_stock_threshold'] : null,
            );
        }

        fclose($handle);

        return $rows;
    }
}
