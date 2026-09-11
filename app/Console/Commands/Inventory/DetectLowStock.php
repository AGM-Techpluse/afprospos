<?php

declare(strict_types=1);

namespace App\Console\Commands\Inventory;

use Domain\Inventory\Application\Commands\DetectLowStockCommand;
use Domain\Inventory\Application\Handlers\DetectLowStockHandler;
use Illuminate\Console\Command;

/**
 * CPNC §2.2: parses CLI args, builds one Command, invokes one Handler,
 * formats output — no business logic in the body itself.
 */
final class DetectLowStock extends Command
{
    protected $signature = 'afprospos:inventory:detect-low-stock {--shop=}';

    protected $description = 'Flags SKUs whose Available stock has fallen to or below their configured low-stock threshold (INV-BR-07).';

    public function __construct(
        private readonly DetectLowStockHandler $handler,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $shopId = $this->option('shop') !== null ? (int) $this->option('shop') : null;

        $items = $this->handler->handle(new DetectLowStockCommand($shopId));

        if ($items === []) {
            $this->info('No low-stock items found.');

            return self::SUCCESS;
        }

        $this->table(
            ['SKU', 'Shop', 'Available', 'Threshold'],
            array_map(static fn (array $item): array => [
                $item['sku_code'],
                $item['shop_id'],
                $item['available'],
                $item['low_stock_threshold'],
            ], $items),
        );

        return self::SUCCESS;
    }
}
