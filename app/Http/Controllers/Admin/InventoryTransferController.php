<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\InitiateInventoryTransferRequest;
use Domain\Inventory\Application\Commands\CancelInventoryTransferCommand;
use Domain\Inventory\Application\Commands\InitiateInventoryTransferCommand;
use Domain\Inventory\Application\Commands\ReceiveInventoryTransferCommand;
use Domain\Inventory\Application\Handlers\CancelInventoryTransferHandler;
use Domain\Inventory\Application\Handlers\InitiateInventoryTransferHandler;
use Domain\Inventory\Application\Handlers\ReceiveInventoryTransferHandler;
use Domain\Inventory\Application\Queries\InventoryTransferQuery;
use Domain\Inventory\Domain\Exceptions\InsufficientAvailableStock;
use Domain\Inventory\Domain\Exceptions\ItemNotTransferable;
use Domain\Shared\Application\DTOs\ActorContext;
use Domain\Shop\Application\Queries\ShopDirectoryQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class InventoryTransferController
{
    public function __construct(
        private readonly InventoryTransferQuery $transfers,
        private readonly ShopDirectoryQuery $shops,
        private readonly InitiateInventoryTransferHandler $initiate,
        private readonly ReceiveInventoryTransferHandler $receiveTransfer,
        private readonly CancelInventoryTransferHandler $cancel,
    ) {}

    public function create(): Response
    {
        return Inertia::render('Admin/Inventory/Transfers/Create', [
            'shops' => $this->shops->all(),
        ]);
    }

    public function index(Request $request): Response
    {
        return Inertia::render('Admin/Inventory/Transfers/Index', [
            'transfers' => $this->transfers->paginate(
                status: $request->string('status')->toString() ?: null,
                shopId: $request->integer('shop_id') ?: null,
                page: $request->integer('page', 1),
            ),
            'filters' => [
                'status' => $request->string('status')->toString(),
                'shop_id' => $request->integer('shop_id') ?: null,
            ],
        ]);
    }

    public function show(int $transfer): Response
    {
        $detail = $this->transfers->find($transfer);

        abort_if($detail === null, 404);

        return Inertia::render('Admin/Inventory/Transfers/Show', ['transfer' => $detail]);
    }

    public function store(InitiateInventoryTransferRequest $request): RedirectResponse
    {
        $actor = app(ActorContext::class);

        try {
            $transferId = $this->initiate->handle(new InitiateInventoryTransferCommand(
                skuId: $request->integer('sku_id'),
                inventoryItemId: $request->integer('inventory_item_id') ?: null,
                quantity: $request->integer('quantity') ?: null,
                fromShopId: $request->integer('from_shop_id'),
                toShopId: $request->integer('to_shop_id'),
                initiatedByStaffId: $actor->staffId->value,
            ));
        } catch (InsufficientAvailableStock|ItemNotTransferable $exception) {
            throw ValidationException::withMessages(['sku_id' => $exception->getMessage()]);
        }

        return redirect()->route('admin.inventory.transfers.show', $transferId->value);
    }

    public function receive(int $transfer): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->receiveTransfer->handle(new ReceiveInventoryTransferCommand(
            transferId: $transfer,
            receivedByStaffId: $actor->staffId->value,
        ));

        return redirect()->route('admin.inventory.transfers.show', $transfer);
    }

    public function cancel(int $transfer): RedirectResponse
    {
        $actor = app(ActorContext::class);

        $this->cancel->handle(new CancelInventoryTransferCommand(
            transferId: $transfer,
            cancelledByStaffId: $actor->staffId->value,
        ));

        return redirect()->route('admin.inventory.transfers.show', $transfer);
    }
}
