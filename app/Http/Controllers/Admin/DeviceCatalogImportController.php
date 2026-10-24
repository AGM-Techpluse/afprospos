<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\ImportDeviceCatalogRequest;
use Domain\Repair\Application\Commands\ImportDeviceCatalogCommand;
use Domain\Repair\Application\DTOs\DeviceCatalogImportRow;
use Domain\Repair\Application\DTOs\DeviceCatalogImportRowResult;
use Domain\Repair\Application\Handlers\ImportDeviceCatalogHandler;
use Domain\Shared\Application\DTOs\ActorContext;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Bulk CSV import for the device catalog — one row upserts as much of the
 * Type -> Brand -> Model -> Fault hierarchy as it specifies. Parsing the
 * raw file is a Controller-level concern (CPNC §2.3); the Command carries
 * already-parsed rows, and one bad row never rolls back the batch
 * (ImportDeviceCatalogHandler).
 */
final class DeviceCatalogImportController
{
    private const EXPECTED_HEADER = ['device_type', 'icon', 'brand', 'model', 'fault_label', 'commission_amount_minor', 'suggested_part_skus'];

    public function __construct(
        private readonly ImportDeviceCatalogHandler $importCatalog,
    ) {}

    public function create(): Response
    {
        return Inertia::render('Admin/Settings/DeviceCatalog/Import/Create', [
            'expectedHeader' => self::EXPECTED_HEADER,
        ]);
    }

    /** Downloadable starting point — the header plus a few worked example rows covering every optional-column combination. */
    public function template(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, self::EXPECTED_HEADER);
            fputcsv($handle, ['Smartphones', 'phone', 'Apple', 'iPhone 15 Pro', 'Screen', '300000', 'IPH15PRO-SCR']);
            fputcsv($handle, ['Smartphones', 'phone', 'Apple', 'iPhone 15 Pro', 'Battery', '150000', 'IPH15PRO-BAT;IPH15PRO-BAT-OEM']);
            fputcsv($handle, ['Smartphones', 'phone', 'Apple', 'iPhone SE', '', '', '']);
            fputcsv($handle, ['Smartphones', 'phone', 'Samsung', '', 'Screen', '100000', '']);
            fclose($handle);
        }, 'afprospos-device-catalog-import-template.csv', ['Content-Type' => 'text/csv']);
    }

    public function store(ImportDeviceCatalogRequest $request): Response
    {
        $actor = app(ActorContext::class);

        [$rows, $preValidationFailures] = $this->parseCsv($request->file('file'));

        $results = $this->importCatalog->handle(new ImportDeviceCatalogCommand(
            rows: $rows,
            importedByStaffId: $actor->staffId->value,
        ));

        $allResults = [...$results, ...$preValidationFailures];
        usort($allResults, static fn (DeviceCatalogImportRowResult $a, DeviceCatalogImportRowResult $b): int => $a->rowNumber <=> $b->rowNumber);

        return Inertia::render('Admin/Settings/DeviceCatalog/Import/Results', [
            'results' => array_map(static fn (DeviceCatalogImportRowResult $result): array => [
                'row_number' => $result->rowNumber,
                'succeeded' => $result->succeeded,
                'summary' => $result->summary,
                'error_message' => $result->errorMessage,
            ], $allResults),
        ]);
    }

    /**
     * Every data row must produce exactly one result — success or failure —
     * so the Results page count always matches the file. A row with fewer
     * columns than the header is padded rather than silently discarded;
     * only a genuinely missing device_type/brand is rejected outright.
     *
     * @return array{0: DeviceCatalogImportRow[], 1: DeviceCatalogImportRowResult[]}
     */
    private function parseCsv(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'rb');
        $header = fgetcsv($handle);

        if ($header !== false) {
            $header = array_map(static fn ($column): string => strtolower(trim((string) $column)), $header);
        }
        $columnCount = $header !== false ? count($header) : 0;
        $rows = [];
        $failures = [];
        $rowNumber = 1;

        while (($csvRow = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if ($header === false) {
                continue;
            }

            if (count($csvRow) < $columnCount) {
                $csvRow = array_pad($csvRow, $columnCount, '');
            } elseif (count($csvRow) > $columnCount) {
                $csvRow = array_slice($csvRow, 0, $columnCount);
            }

            $row = array_combine($header, $csvRow);

            $deviceType = trim((string) ($row['device_type'] ?? ''));
            $brand = trim((string) ($row['brand'] ?? ''));

            if ($deviceType === '' || $brand === '') {
                $failures[] = new DeviceCatalogImportRowResult($rowNumber, false, null, 'Row is missing a required device_type or brand value.');

                continue;
            }

            $model = trim((string) ($row['model'] ?? ''));
            $faultLabel = trim((string) ($row['fault_label'] ?? ''));
            $commission = trim((string) ($row['commission_amount_minor'] ?? ''));
            $suggestedSkus = trim((string) ($row['suggested_part_skus'] ?? ''));

            $rows[] = new DeviceCatalogImportRow(
                rowNumber: $rowNumber,
                deviceTypeLabel: $deviceType,
                icon: trim((string) ($row['icon'] ?? '')) ?: 'device-hdd',
                brandName: $brand,
                modelName: $model !== '' ? $model : null,
                faultLabel: $faultLabel !== '' ? $faultLabel : null,
                commissionAmountMinor: $commission !== '' ? (int) $commission : null,
                suggestedSkuCodes: $suggestedSkus !== '' ? array_values(array_filter(array_map('trim', explode(';', $suggestedSkus)))) : null,
            );
        }

        fclose($handle);

        return [$rows, $failures];
    }
}
