<?php

declare(strict_types=1);

namespace App\Console\Commands\Demo;

use App\Support\Images\ProductImageProcessor;
use Domain\Inventory\Application\Commands\CreateProductCommand;
use Domain\Inventory\Application\Handlers\CreateProductHandler;
use Domain\Inventory\Domain\Repositories\InventoryMasterRepository;
use Domain\Inventory\Infrastructure\Persistence\Eloquent\SkuRecord;
use Domain\Repair\Application\Commands\ImportDeviceCatalogCommand;
use Domain\Repair\Application\DTOs\DeviceCatalogImportRow;
use Domain\Repair\Application\Handlers\ImportDeviceCatalogHandler;
use Domain\Shared\Infrastructure\Persistence\Eloquent\ShopRecord;
use Domain\Shared\Infrastructure\Persistence\Eloquent\StaffRecord;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * One-off demo-data tool, not a durable application feature — curated
 * product data lives here in-code rather than as a hand-maintained CSV,
 * since the CSV is itself an *output* of running this (see
 * documentation/afprospos-demo-storefront-catalog.csv). Safe to re-run:
 * every write path it uses (CreateProductHandler's product find-or-create,
 * ImportDeviceCatalogHandler's whole chain) is already idempotent.
 */
final class SeedStorefrontCatalog extends Command
{
    protected $signature = 'afprospos:demo:seed-storefront-catalog {shop : Shop id to stock the demo products at}';

    protected $description = 'Seeds a curated demo catalog (10 phones/laptops/accessories/gadgets + matching repair parts and faults), fetching a real photo per item where possible, and writes a CSV artifact.';

    /** @var array<int, array<string, mixed>> */
    private array $csvRows = [];

    /** @var DeviceCatalogImportRow[] */
    private array $deviceCatalogRows = [];

    private int $deviceCatalogRowNumber = 0;

    private int $imeiCounter = 0;

    public function __construct(
        private readonly CreateProductHandler $createProduct,
        private readonly ImportDeviceCatalogHandler $importDeviceCatalog,
        private readonly InventoryMasterRepository $masters,
        private readonly ProductImageProcessor $images,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        // Some Openverse-linked originals (Wikimedia Commons photos especially) are
        // multi-megapixel — GD's decode buffer for those can exceed the CLI's default
        // 128M and dies as an uncatchable fatal error mid-batch. Raised here rather than
        // in php.ini, since this is the one place in the app that decodes arbitrary
        // third-party images.
        ini_set('memory_limit', '512M');

        $shop = ShopRecord::query()->find((int) $this->argument('shop'));

        if ($shop === null) {
            $this->components->error("Shop [{$this->argument('shop')}] does not exist.");

            return self::FAILURE;
        }

        $staffId = StaffRecord::query()->value('id');

        if ($staffId === null) {
            $this->components->error('No staff records exist to attribute this seed to — create one first.');

            return self::FAILURE;
        }

        foreach (['Phones', 'Laptops', 'Accessories', 'Smartwatches', 'Tablets', 'Consoles', 'Cameras', 'Drones'] as $category) {
            $this->ensureCategory($category);
        }

        $phones = $this->phones();
        $laptops = $this->laptops();

        $this->components->task('Sellable phones', fn () => $this->seedEach($phones, 'Phones', $shop, (int) $staffId));
        $this->components->task('Sellable laptops', fn () => $this->seedEach($laptops, 'Laptops', $shop, (int) $staffId));
        $this->components->task('Accessories', fn () => $this->seedEach($this->accessories(), 'Accessories', $shop, (int) $staffId));
        $this->components->task('Gadgets', fn () => $this->seedEach($this->gadgets(), null, $shop, (int) $staffId));

        $this->components->task('Phone repair parts + faults', function () use ($phones, $shop, $staffId): bool {
            foreach ($phones as $phone) {
                $this->seedDeviceParts($phone, 'Smartphones', 'phone', $shop, (int) $staffId);
            }

            return true;
        });

        $this->components->task('Laptop repair parts + faults', function () use ($laptops, $shop, $staffId): bool {
            foreach ($laptops as $laptop) {
                $this->seedDeviceParts($laptop, 'Laptops', 'laptop', $shop, (int) $staffId);
            }

            return true;
        });

        $this->components->task('Gadget repair parts', fn () => $this->seedGadgetParts($this->gadgets(), $shop, (int) $staffId));

        if ($this->deviceCatalogRows !== []) {
            $this->importDeviceCatalog->handle(new ImportDeviceCatalogCommand($this->deviceCatalogRows, (int) $staffId));
        }

        $csvPath = base_path('documentation/afprospos-demo-storefront-catalog.csv');
        $this->writeCsv($csvPath);
        $this->components->info("Wrote {$csvPath} (".count($this->csvRows).' rows).');

        return self::SUCCESS;
    }

    // --- Curated data -------------------------------------------------

    /** @return array<int, array{brand:string, model:string, cost:int}> */
    private function phones(): array
    {
        return [
            ['brand' => 'Apple', 'model' => 'iPhone 15 Pro', 'cost' => 650_000],
            ['brand' => 'Apple', 'model' => 'iPhone 14', 'cost' => 450_000],
            ['brand' => 'Apple', 'model' => 'iPhone SE', 'cost' => 280_000],
            ['brand' => 'Samsung', 'model' => 'Galaxy S24 Ultra', 'cost' => 700_000],
            ['brand' => 'Samsung', 'model' => 'Galaxy A54', 'cost' => 250_000],
            ['brand' => 'Google', 'model' => 'Pixel 8 Pro', 'cost' => 550_000],
            ['brand' => 'Xiaomi', 'model' => 'Redmi Note 13 Pro', 'cost' => 180_000],
            ['brand' => 'OnePlus', 'model' => '12', 'cost' => 480_000],
            ['brand' => 'Tecno', 'model' => 'Camon 20', 'cost' => 150_000],
            ['brand' => 'Infinix', 'model' => 'Note 30', 'cost' => 120_000],
        ];
    }

    /** @return array<int, array{brand:string, model:string, cost:int}> */
    private function laptops(): array
    {
        return [
            ['brand' => 'Apple', 'model' => 'MacBook Air M2', 'cost' => 950_000],
            ['brand' => 'Apple', 'model' => 'MacBook Pro 14', 'cost' => 1_600_000],
            ['brand' => 'Dell', 'model' => 'XPS 13', 'cost' => 850_000],
            ['brand' => 'Dell', 'model' => 'Inspiron 15', 'cost' => 400_000],
            ['brand' => 'HP', 'model' => 'Pavilion 15', 'cost' => 420_000],
            ['brand' => 'HP', 'model' => 'EliteBook 840', 'cost' => 650_000],
            ['brand' => 'Lenovo', 'model' => 'ThinkPad X1 Carbon', 'cost' => 900_000],
            ['brand' => 'Lenovo', 'model' => 'IdeaPad 3', 'cost' => 320_000],
            ['brand' => 'Asus', 'model' => 'ZenBook 14', 'cost' => 550_000],
            ['brand' => 'Acer', 'model' => 'Aspire 5', 'cost' => 350_000],
        ];
    }

    /** @return array<int, array{brand:string, model:string, cost:int}> */
    private function accessories(): array
    {
        return [
            ['brand' => 'Anker', 'model' => 'PowerCore 20000', 'cost' => 18_000],
            ['brand' => 'Anker', 'model' => '65W USB-C Charger', 'cost' => 15_000],
            ['brand' => 'Apple', 'model' => 'MagSafe Charger', 'cost' => 22_000],
            ['brand' => 'Samsung', 'model' => '25W Fast Charger', 'cost' => 12_000],
            ['brand' => 'JBL', 'model' => 'Tune 510BT Headphones', 'cost' => 20_000],
            ['brand' => 'Sony', 'model' => 'WH-CH520 Headphones', 'cost' => 25_000],
            ['brand' => 'Baseus', 'model' => 'Car Phone Mount', 'cost' => 6_000],
            ['brand' => 'Spigen', 'model' => 'Tough Armor Case', 'cost' => 9_000],
            ['brand' => 'UGREEN', 'model' => 'USB-C Hub', 'cost' => 16_000],
            ['brand' => 'Logitech', 'model' => 'M185 Wireless Mouse', 'cost' => 8_000],
        ];
    }

    /** @return array<int, array{brand:string, model:string, cost:int, category:string}> */
    private function gadgets(): array
    {
        return [
            ['brand' => 'Apple', 'model' => 'Watch Series 9', 'cost' => 350_000, 'category' => 'Smartwatches'],
            ['brand' => 'Samsung', 'model' => 'Galaxy Watch 6', 'cost' => 280_000, 'category' => 'Smartwatches'],
            ['brand' => 'Apple', 'model' => 'iPad 10th Gen', 'cost' => 400_000, 'category' => 'Tablets'],
            ['brand' => 'Samsung', 'model' => 'Galaxy Tab S9', 'cost' => 450_000, 'category' => 'Tablets'],
            ['brand' => 'Sony', 'model' => 'PlayStation 5', 'cost' => 550_000, 'category' => 'Consoles'],
            ['brand' => 'Microsoft', 'model' => 'Xbox Series S', 'cost' => 300_000, 'category' => 'Consoles'],
            ['brand' => 'Nintendo', 'model' => 'Switch OLED', 'cost' => 320_000, 'category' => 'Consoles'],
            ['brand' => 'Canon', 'model' => 'EOS M50', 'cost' => 480_000, 'category' => 'Cameras'],
            ['brand' => 'GoPro', 'model' => 'Hero 12', 'cost' => 380_000, 'category' => 'Cameras'],
            ['brand' => 'DJI', 'model' => 'Mini 4 Pro', 'cost' => 650_000, 'category' => 'Drones'],
        ];
    }

    // --- Seeding --------------------------------------------------------

    /** @param  array<int, array{brand:string, model:string, cost:int, category?:string}>  $items */
    private function seedEach(array $items, ?string $category, ShopRecord $shop, int $staffId): bool
    {
        foreach ($items as $item) {
            $this->seedProduct(
                brand: $item['brand'],
                model: $item['model'],
                category: $category ?? $item['category'],
                costPriceMinor: $item['cost'] * 100,
                markupPercent: 20.0,
                purpose: 'sale',
                isSerialized: true,
                quantity: null,
                shop: $shop,
                staffId: $staffId,
            );
        }

        return true;
    }

    private function seedDeviceParts(array $device, string $typeLabel, string $icon, ShopRecord $shop, int $staffId): void
    {
        $partCost = (int) round($device['cost'] * 0.15);

        $faults = [
            'Screen' => $this->seedProduct(
                brand: $device['brand'], model: "{$device['model']} Screen", category: $typeLabel === 'Laptops' ? 'Laptops' : 'Phones',
                costPriceMinor: $partCost * 100, markupPercent: 35.0, purpose: 'repair_part', isSerialized: false, quantity: 10,
                shop: $shop, staffId: $staffId,
            ),
            'Battery' => $this->seedProduct(
                brand: $device['brand'], model: "{$device['model']} Battery", category: $typeLabel === 'Laptops' ? 'Laptops' : 'Phones',
                costPriceMinor: (int) round($partCost * 0.5) * 100, markupPercent: 35.0, purpose: 'repair_part', isSerialized: false, quantity: 10,
                shop: $shop, staffId: $staffId,
            ),
            'Charging port' => $this->seedProduct(
                brand: $device['brand'], model: "{$device['model']} Charging Port", category: $typeLabel === 'Laptops' ? 'Laptops' : 'Phones',
                costPriceMinor: (int) round($partCost * 0.3) * 100, markupPercent: 35.0, purpose: 'repair_part', isSerialized: false, quantity: 10,
                shop: $shop, staffId: $staffId,
            ),
        ];

        $commissions = ['Screen' => 500000, 'Battery' => 200000, 'Charging port' => 150000];

        foreach ($faults as $label => $skuCode) {
            if ($skuCode === null) {
                continue;
            }

            $this->deviceCatalogRows[] = new DeviceCatalogImportRow(
                rowNumber: ++$this->deviceCatalogRowNumber,
                deviceTypeLabel: $typeLabel,
                icon: $icon,
                brandName: $device['brand'],
                modelName: $device['model'],
                faultLabel: $label,
                commissionAmountMinor: $commissions[$label],
                suggestedSkuCodes: [$skuCode],
            );
        }
    }

    /** @param  array<int, array{brand:string, model:string, cost:int, category:string}>  $gadgets */
    private function seedGadgetParts(array $gadgets, ShopRecord $shop, int $staffId): bool
    {
        foreach ($gadgets as $gadget) {
            $this->seedProduct(
                brand: $gadget['brand'], model: "{$gadget['model']} Replacement Part", category: $gadget['category'],
                costPriceMinor: (int) round($gadget['cost'] * 0.2) * 100, markupPercent: 35.0, purpose: 'repair_part', isSerialized: false, quantity: 5,
                shop: $shop, staffId: $staffId,
            );
        }

        return true;
    }

    /** Creates the product (with a best-effort fetched photo) and records both the Inertia-import CSV row and, for sellable items, nothing else — returns the resulting sku_code for repair-part items, so the fault-import step can reference it directly instead of round-tripping through CSV text. */
    private function seedProduct(
        string $brand,
        string $model,
        string $category,
        int $costPriceMinor,
        float $markupPercent,
        string $purpose,
        bool $isSerialized,
        ?int $quantity,
        ShopRecord $shop,
        int $staffId,
    ): ?string {
        $imagePath = $this->fetchImage("{$brand} {$model}", $category);

        $imei = $isSerialized ? $this->nextImei() : null;

        $skuId = $this->createProduct->handle(new CreateProductCommand(
            brand: $brand,
            model: $model,
            category: $category,
            attributes: [],
            isSerialized: $isSerialized,
            costPriceMinor: $costPriceMinor,
            markupPercent: $markupPercent,
            sellingPriceMinorOverride: null,
            lowStockThreshold: 3,
            shopCode: (string) $shop->sku_prefix_code,
            shopId: $shop->id,
            condition: 'new',
            initialQuantity: $isSerialized ? null : ($quantity ?? 5),
            initialImeis: $imei !== null ? [$imei] : null,
            initialSerialNumbers: null,
            createdByStaffId: $staffId,
            unit: 'pcs',
            purpose: $purpose,
            imagePath: $imagePath,
        ));

        $skuCode = SkuRecord::query()->whereKey($skuId->value)->value('sku_code');

        $this->csvRows[] = [
            'brand' => $brand,
            'model' => $model,
            'category' => $category,
            'condition' => 'new',
            'cost_price_minor' => $costPriceMinor,
            'markup_percent' => $markupPercent,
            'quantity' => $isSerialized ? '' : (string) ($quantity ?? 5),
            'imei' => $imei ?? '',
            'low_stock_threshold' => '3',
            'unit' => 'pcs',
            'purpose' => $purpose,
            'supplier' => '',
            'color' => '',
            'storage' => '',
            'compatibility' => '',
        ];

        return $skuCode;
    }

    // --- Support ----------------------------------------------------

    private function ensureCategory(string $name): void
    {
        if (! $this->masters->exists('category', $name)) {
            $this->masters->create('category', $name, null, null);
        }
    }

    private function nextImei(): string
    {
        $this->imeiCounter++;

        return '99'.str_pad((string) $this->imeiCounter, 13, '0', STR_PAD_LEFT);
    }

    /** Openverse (and several of the CDNs it links out to, e.g. Flickr) reset connections from clients with no descriptive User-Agent — Cloudflare/WAF behavior, not a real outage. */
    private const USER_AGENT = 'AfProsPosDemoSeeder/1.0 (+https://afprospos.test; demo data seeder)';

    /** Above this pixel count, skip rather than hand the bytes to GD — a decode buffer this size plus the rest of a long-running CLI process can still blow past even the raised 512M limit, and a bad allocation inside the GD extension is not reliably catchable. 25MP covers any realistic product photo. */
    private const MAX_SOURCE_PIXELS = 25_000_000;

    /** Best effort, never fatal — a failed/empty fetch just leaves the product imageless; the storefront's own placeholder covers that already. */
    private function fetchImage(string $primaryTerm, string $fallbackTerm): ?string
    {
        foreach ([$primaryTerm, $fallbackTerm] as $term) {
            try {
                $search = Http::withHeaders(['User-Agent' => self::USER_AGENT])->timeout(20)->get('https://api.openverse.org/v1/images/', [
                    'q' => $term,
                    'page_size' => 1,
                    'mature' => 'false',
                ]);

                if (! $search->successful()) {
                    continue;
                }

                $result = $search->json('results.0');

                if (! is_array($result)) {
                    continue;
                }

                // Openverse's own thumbnail proxy is smaller and faster, but itself
                // unreliable (it depends on a third-party resize proxy that sometimes
                // 4xxs) — fall back to the full-size original on that specific failure.
                $body = null;
                $contentType = null;

                foreach (array_filter([$result['thumbnail'] ?? null, $result['url'] ?? null]) as $candidateUrl) {
                    $download = Http::withHeaders(['User-Agent' => self::USER_AGENT])->timeout(20)->get($candidateUrl);

                    if ($download->successful()) {
                        $body = $download->body();
                        $contentType = $download->header('Content-Type');

                        break;
                    }
                }

                if ($body === null) {
                    continue;
                }

                $dimensions = @getimagesizefromstring($body);

                if ($dimensions === false || $dimensions[0] * $dimensions[1] > self::MAX_SOURCE_PIXELS) {
                    continue;
                }

                $tempPath = tempnam(sys_get_temp_dir(), 'demo-img-');
                file_put_contents($tempPath, $body);

                $uploaded = new UploadedFile($tempPath, 'photo.jpg', $contentType ?: 'image/jpeg', null, true);

                return $this->images->store($uploaded, 'inventory/products');
            } catch (Throwable) {
                continue;
            }
        }

        return null;
    }

    /** A ~25-minute run (mostly image fetching) is too expensive to redo just because a transient Windows file lock (e.g. an antivirus scan, or the CSV sitting open in an editor) loses the very last write — so this retries a few times with a short wait before giving up. */
    private function writeCsv(string $path): void
    {
        $handle = false;
        $attempts = 0;

        while ($handle === false && $attempts < 5) {
            $handle = @fopen($path, 'wb');

            if ($handle === false) {
                $attempts++;
                usleep(500_000);
            }
        }

        if ($handle === false) {
            $this->components->error("Could not write {$path} — it may be open in another program. Close it and re-run just this write with: php artisan tinker, or re-run the whole command (it's safe to repeat).");

            return;
        }

        fputcsv($handle, ['brand', 'model', 'category', 'condition', 'cost_price_minor', 'markup_percent', 'quantity', 'imei', 'low_stock_threshold', 'unit', 'purpose', 'supplier', 'color', 'storage', 'compatibility']);

        foreach ($this->csvRows as $row) {
            fputcsv($handle, array_values($row));
        }

        fclose($handle);
    }
}
