<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\FlashesToast;
use App\Http\Requests\Admin\AdjustStockRequest;
use App\Http\Requests\Admin\BulkAdjustStockRequest;
use App\Http\Requests\Admin\BulkInitiateInventoryTransferRequest;
use App\Http\Requests\Admin\ReceiveStockRequest;
use Domain\Inventory\Application\Commands\AdjustStockCommand;
use Domain\Inventory\Application\Commands\InitiateInventoryTransferCommand;
use Domain\Inventory\Application\Commands\ReceiveStockCommand;
use Domain\Inventory\Application\Handlers\AdjustStockHandler;
use Domain\Inventory\Application\Handlers\InitiateInventoryTransferHandler;
use Domain\Inventory\Application\Handlers\ReceiveStockHandler;
use Domain\Inventory\Application\Queries\LowStockQuery;
use Domain\Inventory\Application\Queries\StockLevelQuery;
use Domain\Inventory\Domain\Exceptions\ImeiAlreadyExists;
use Domain\Shared\Application\DTOs\ActorContext;
use Domain\Shop\Application\Queries\ShopDirectoryQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

final class InventoryStockController
{
    use FlashesToast;

    public function __construct(
        private readonly StockLevelQuery $stockLevels,
        private readonly LowStockQuery $lowStock,
        private readonly ShopDirectoryQuery $shops,
        private readonly ReceiveStockHandler $receiveStock,
        private readonly AdjustStockHandler $adjustStock,
        private readonly InitiateInventoryTransferHandler $initiateTransfer,
    ) {}

    public function index(Request $request): Response
    {
        $actor = app(ActorContext::class);
        $shopId = $request->has('shop_id') ? ($request->integer('shop_id') ?: null) : $actor->activeShopId;

        return Inertia::render('Admin/Inventory/Stock/Index', [
            'stock' => $this->stockLevels->paginate(
                search: $request->string('search')->toString() ?: null,
                shopId: $shopId,
                page: $request->integer('page', 1),
            ),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'shop_id' => $shopId,
            ],
            'shops' => $this->shops->all(),
        ]);
    }

    public function lowStock(Request $request): Response
    {
        $shopId = $request->integer('shop_id') ?: null;

        return Inertia::render('Admin/Inventory/Stock/LowStock', [
            'items' => $this->lowStock->forShop($shopId),
            'filters' => ['shop_id' => $shopId],
            'shops' => $this->shops->all(),
        ]);
    }

    public function receive(ReceiveStockRequest $request, int $sku): RedirectResponse
    {
        $actor = app(ActorContext::class);

        try {
            $this->receiveStock->handle(new ReceiveStockCommand(
                skuId: $sku,
                shopId: $request->integer('shop_id'),
                condition: $request->string('condition')->toString(),
                quantity: $request->integer('quantity') ?: null,
                imeis: $request->array('imeis') ?: null,
                receivedByStaffId: $actor->staffId->value,
            ));
        } catch (ImeiAlreadyExists $exception) {
            throw ValidationException::withMessages(['imeis' => $exception->getMessage()]);
        }

        $this->flashSuccess('Stock received');

        return redirect()->route('admin.inventory.products.show', $sku);
    }

    public function adjustForm(int $sku, int $shop): Response
    {
        $level = $this->stockLevels->find($sku, $shop);

        abort_if($level === null, 404);

        return Inertia::render('Admin/Inventory/Stock/Adjust', ['level' => $level, 'shop' => $this->shops->find($shop)]);
    }

    public function adjust(AdjustStockRequest $request, int $sku, int $shop): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->adjustStock->handle(new AdjustStockCommand(
            skuId: $sku,
            shopId: $shop,
            delta: $request->integer('delta'),
            reason: $request->string('reason')->toString(),
            adjustedByStaffId: $actor->staffId->value,
        ));

        $this->flashSuccess('Stock adjusted');

        return redirect()->route('admin.inventory.stock.index');
    }

    /**
     * Each line is its own AdjustStockHandler call/transaction — a bulk
     * action is N independent domain actions, not one new business
     * concept, and per-row locking avoids one giant multi-row lock.
     * One bad row never rolls back the batch (mirrors
     * InventoryImportController's row-result pattern).
     */
    public function bulkAdjust(BulkAdjustStockRequest $request): RedirectResponse
    {
        $actor = app(ActorContext::class);
        $reason = $request->string('reason')->toString();
        $succeeded = 0;
        $failures = [];

        foreach ($request->array('items') as $item) {
            try {
                $this->adjustStock->handle(new AdjustStockCommand(
                    skuId: (int) $item['sku_id'],
                    shopId: (int) $item['shop_id'],
                    delta: (int) $item['delta'],
                    reason: $reason,
                    adjustedByStaffId: $actor->staffId->value,
                ));
                $succeeded++;
            } catch (Throwable $exception) {
                $failures[] = "SKU #{$item['sku_id']}: {$exception->getMessage()}";
            }
        }

        $this->flashBulkResult($succeeded, $failures, 'adjusted');

        return redirect()->route('admin.inventory.stock.index');
    }

    public function bulkTransfer(BulkInitiateInventoryTransferRequest $request): RedirectResponse
    {
        $actor = app(ActorContext::class);
        $toShopId = $request->integer('to_shop_id');
        $succeeded = 0;
        $failures = [];

        foreach ($request->array('items') as $item) {
            try {
                $this->initiateTransfer->handle(new InitiateInventoryTransferCommand(
                    skuId: (int) $item['sku_id'],
                    inventoryItemId: null,
                    quantity: (int) $item['quantity'],
                    fromShopId: (int) $item['from_shop_id'],
                    toShopId: $toShopId,
                    initiatedByStaffId: $actor->staffId->value,
                ));
                $succeeded++;
            } catch (Throwable $exception) {
                $failures[] = "SKU #{$item['sku_id']}: {$exception->getMessage()}";
            }
        }

        $this->flashBulkResult($succeeded, $failures, 'transferred');

        return redirect()->route('admin.inventory.stock.index');
    }

    /** @param  string[]  $failures */
    private function flashBulkResult(int $succeeded, array $failures, string $verb): void
    {
        $total = $succeeded + count($failures);

        if ($failures === []) {
            $this->flashSuccess("{$succeeded} of {$total} {$verb}");

            return;
        }

        $this->flashWarning("{$succeeded} of {$total} {$verb}", implode(' · ', array_slice($failures, 0, 3)));
    }
}
