<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\AdjustStockRequest;
use App\Http\Requests\Admin\ReceiveStockRequest;
use Domain\Inventory\Application\Commands\AdjustStockCommand;
use Domain\Inventory\Application\Commands\ReceiveStockCommand;
use Domain\Inventory\Application\Handlers\AdjustStockHandler;
use Domain\Inventory\Application\Handlers\ReceiveStockHandler;
use Domain\Inventory\Application\Queries\LowStockQuery;
use Domain\Inventory\Application\Queries\StockLevelQuery;
use Domain\Inventory\Domain\Exceptions\ImeiAlreadyExists;
use Domain\Shared\Application\DTOs\ActorContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class InventoryStockController
{
    public function __construct(
        private readonly StockLevelQuery $stockLevels,
        private readonly LowStockQuery $lowStock,
        private readonly ReceiveStockHandler $receiveStock,
        private readonly AdjustStockHandler $adjustStock,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Admin/Inventory/Stock/Index', [
            'stock' => $this->stockLevels->paginate(
                search: $request->string('search')->toString() ?: null,
                shopId: $request->integer('shop_id') ?: null,
                page: $request->integer('page', 1),
            ),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'shop_id' => $request->integer('shop_id') ?: null,
            ],
        ]);
    }

    public function lowStock(Request $request): Response
    {
        return Inertia::render('Admin/Inventory/Stock/LowStock', [
            'items' => $this->lowStock->forShop($request->integer('shop_id') ?: null),
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

        return redirect()->route('admin.inventory.products.show', $sku);
    }

    public function adjustForm(int $sku, int $shop): Response
    {
        $level = $this->stockLevels->find($sku, $shop);

        abort_if($level === null, 404);

        return Inertia::render('Admin/Inventory/Stock/Adjust', ['level' => $level]);
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

        return redirect()->route('admin.inventory.stock.index');
    }
}
