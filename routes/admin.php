<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InventoryImportController;
use App\Http\Controllers\Admin\InventoryStockController;
use App\Http\Controllers\Admin\InventoryTransferController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SalesController;
use App\Http\Controllers\Admin\ShopController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Auth\StaffAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Staff guest routes (login)
|--------------------------------------------------------------------------
*/
Route::middleware('guest:staff')->group(function (): void {
    Route::get('/staff/login', [StaffAuthController::class, 'create'])->name('staff.login');
    Route::post('/staff/login', [StaffAuthController::class, 'store'])->name('staff.login.store');
});

Route::post('/staff/logout', [StaffAuthController::class, 'destroy'])
    ->middleware('auth:staff')
    ->name('staff.logout');

/*
|--------------------------------------------------------------------------
| Authenticated admin routes
|--------------------------------------------------------------------------
|
| Middleware order matters (ADD §26.2):
|   auth:staff        -> is there a logged-in staff session at all?
|   staff.active      -> is that account still active RIGHT NOW?
|   shop.context      -> resolve + bind ActorContext for this request
|   permission:<perm> -> (per-route) does the actor's effective
|                        permission set include this permission?
|
*/
Route::prefix('admin')
    ->middleware(['auth:staff', 'staff.active', 'shop.context'])
    ->name('admin.')
    ->group(function (): void {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        Route::middleware('permission:staff.view')
            ->get('/staff', [StaffController::class, 'index'])
            ->name('staff.index');

        Route::middleware('permission:staff.create')
            ->get('/staff/create', [StaffController::class, 'create'])
            ->name('staff.create');

        Route::middleware('permission:staff.view')
            ->get('/staff/roles', [RoleController::class, 'index'])
            ->name('staff.roles.index');

        Route::middleware('permission:staff.assign')
            ->get('/staff/roles/create', [RoleController::class, 'create'])
            ->name('staff.roles.create');

        Route::middleware('permission:staff.assign')
            ->post('/staff/roles', [RoleController::class, 'store'])
            ->name('staff.roles.store');

        Route::middleware('permission:staff.assign')
            ->get('/staff/roles/{role}/edit', [RoleController::class, 'edit'])
            ->name('staff.roles.edit');

        Route::middleware('permission:staff.assign')
            ->put('/staff/roles/{role}', [RoleController::class, 'update'])
            ->name('staff.roles.update');

        Route::middleware('permission:staff.create')
            ->post('/staff', [StaffController::class, 'store'])
            ->name('staff.store');

        Route::middleware('permission:staff.view')
            ->get('/staff/{staff}', [StaffController::class, 'show'])
            ->whereNumber('staff')
            ->name('staff.show');

        Route::middleware('permission:staff.edit')
            ->get('/staff/{staff}/edit', [StaffController::class, 'edit'])
            ->whereNumber('staff')
            ->name('staff.edit');

        Route::middleware('permission:staff.edit')
            ->put('/staff/{staff}', [StaffController::class, 'update'])
            ->whereNumber('staff')
            ->name('staff.update');

        Route::middleware('permission:staff.assign')
            ->post('/staff/{staff}/roles', [StaffController::class, 'assignRole'])
            ->name('staff.roles.assign');

        Route::middleware('permission:staff.assign')
            ->delete('/staff/{staff}/roles', [StaffController::class, 'revokeRole'])
            ->name('staff.roles.revoke');

        Route::middleware('permission:staff.assign')
            ->post('/staff/{staff}/shops', [StaffController::class, 'grantShopAccess'])
            ->name('staff.shops.grant');

        Route::middleware('permission:staff.assign')
            ->delete('/staff/{staff}/shops', [StaffController::class, 'revokeShopAccess'])
            ->name('staff.shops.revoke');

        Route::middleware('permission:staff.deactivate')
            ->post('/staff/{staff}/deactivate', [StaffController::class, 'deactivate'])
            ->name('staff.deactivate');

        Route::middleware('permission:shops.view')
            ->get('/shops', [ShopController::class, 'index'])
            ->name('shops.index');

        Route::middleware('permission:shops.create')
            ->get('/shops/create', [ShopController::class, 'create'])
            ->name('shops.create');

        Route::middleware('permission:shops.create')
            ->post('/shops', [ShopController::class, 'store'])
            ->name('shops.store');

        Route::middleware('permission:shops.view')
            ->get('/shops/{shop}', [ShopController::class, 'show'])
            ->whereNumber('shop')
            ->name('shops.show');

        Route::middleware('permission:shops.edit')
            ->get('/shops/{shop}/edit', [ShopController::class, 'edit'])
            ->whereNumber('shop')
            ->name('shops.edit');

        Route::middleware('permission:shops.edit')
            ->put('/shops/{shop}', [ShopController::class, 'update'])
            ->whereNumber('shop')
            ->name('shops.update');

        // Not permission-gated: any authenticated staff member may switch
        // among shops they already hold an active grant for (or, for the
        // owner, any active shop). The actual scope check happens inside
        // SwitchActiveShopHandler via RBAC's ShopScopePolicy — this route
        // deliberately does not duplicate that check.
        Route::post('/shops/switch', [ShopController::class, 'switch'])->name('shops.switch');

        /*
        |----------------------------------------------------------------
        | Inventory (Phase 3)
        |----------------------------------------------------------------
        */
        Route::middleware('permission:inventory.view')
            ->get('/inventory/products', [ProductController::class, 'index'])
            ->name('inventory.products.index');

        Route::middleware('permission:inventory.create')
            ->get('/inventory/products/create', [ProductController::class, 'create'])
            ->name('inventory.products.create');

        Route::middleware('permission:inventory.create')
            ->post('/inventory/products', [ProductController::class, 'store'])
            ->name('inventory.products.store');

        Route::middleware('permission:inventory.view')
            ->get('/inventory/products/{product}', [ProductController::class, 'show'])
            ->whereNumber('product')
            ->name('inventory.products.show');

        Route::middleware('permission:inventory.create')
            ->post('/inventory/products/{sku}/receive', [InventoryStockController::class, 'receive'])
            ->whereNumber('sku')
            ->name('inventory.products.receive');

        Route::middleware('permission:inventory.view')
            ->get('/inventory/stock', [InventoryStockController::class, 'index'])
            ->name('inventory.stock.index');

        Route::middleware('permission:inventory.view')
            ->get('/inventory/stock/low', [InventoryStockController::class, 'lowStock'])
            ->name('inventory.stock.low');

        Route::middleware('permission:inventory.edit')
            ->get('/inventory/stock/{sku}/{shop}/adjust', [InventoryStockController::class, 'adjustForm'])
            ->whereNumber(['sku', 'shop'])
            ->name('inventory.stock.adjust.form');

        Route::middleware('permission:inventory.edit')
            ->post('/inventory/stock/{sku}/{shop}/adjust', [InventoryStockController::class, 'adjust'])
            ->whereNumber(['sku', 'shop'])
            ->name('inventory.stock.adjust');

        Route::middleware('permission:inventory.create')
            ->get('/inventory/import', [InventoryImportController::class, 'create'])
            ->name('inventory.import.create');

        Route::middleware('permission:inventory.create')
            ->post('/inventory/import', [InventoryImportController::class, 'store'])
            ->name('inventory.import.store');

        Route::middleware('permission:inventory.create')
            ->get('/inventory/import/template', [InventoryImportController::class, 'template'])
            ->name('inventory.import.template');

        Route::middleware('permission:inventory.view')
            ->get('/inventory/transfers', [InventoryTransferController::class, 'index'])
            ->name('inventory.transfers.index');

        Route::middleware('permission:inventory.edit')
            ->get('/inventory/transfers/create', [InventoryTransferController::class, 'create'])
            ->name('inventory.transfers.create');

        Route::middleware('permission:inventory.edit')
            ->post('/inventory/transfers', [InventoryTransferController::class, 'store'])
            ->name('inventory.transfers.store');

        Route::middleware('permission:inventory.view')
            ->get('/inventory/transfers/{transfer}', [InventoryTransferController::class, 'show'])
            ->whereNumber('transfer')
            ->name('inventory.transfers.show');

        Route::middleware('permission:inventory.edit')
            ->post('/inventory/transfers/{transfer}/receive', [InventoryTransferController::class, 'receive'])
            ->whereNumber('transfer')
            ->name('inventory.transfers.receive');

        Route::middleware('permission:inventory.edit')
            ->post('/inventory/transfers/{transfer}/cancel', [InventoryTransferController::class, 'cancel'])
            ->whereNumber('transfer')
            ->name('inventory.transfers.cancel');

        Route::middleware('permission:sales.view')
            ->get('/sales', [SalesController::class, 'index'])
            ->name('sales.index');

        Route::middleware('permission:sales.view')
            ->get('/sales/{sale}', [SalesController::class, 'show'])
            ->whereNumber('sale')
            ->name('sales.show');

        Route::middleware('permission:sales.view')
            ->get('/sales/{sale}/receipt', [SalesController::class, 'receipt'])
            ->whereNumber('sale')
            ->name('sales.receipt');

        Route::middleware('permission:sales.create')
            ->get('/sales/checkout', [SalesController::class, 'checkout'])
            ->name('sales.checkout');

        Route::middleware('permission:sales.create')
            ->get('/sales/search', [SalesController::class, 'search'])
            ->name('sales.search');

        Route::middleware('permission:sales.create')
            ->post('/sales/checkout', [SalesController::class, 'store'])
            ->name('sales.checkout.store');

        Route::middleware('permission:sales.create')
            ->post('/sales/checkout/{checkout}/items', [SalesController::class, 'addItem'])
            ->whereNumber('checkout')
            ->name('sales.checkout.items.store');

        Route::middleware('permission:sales.create')
            ->delete('/sales/checkout/{checkout}/items/{item}', [SalesController::class, 'removeItem'])
            ->whereNumber(['checkout', 'item'])
            ->name('sales.checkout.items.destroy');

        Route::middleware('permission:sales.create')
            ->post('/sales/checkout/{checkout}/discount', [SalesController::class, 'applyDiscount'])
            ->whereNumber('checkout')
            ->name('sales.checkout.discount');

        Route::middleware('permission:sales.create')
            ->post('/sales/checkout/{checkout}/complete', [SalesController::class, 'complete'])
            ->whereNumber('checkout')
            ->name('sales.checkout.complete');

        Route::middleware('permission:sales.cancel')
            ->post('/sales/checkout/{checkout}/cancel', [SalesController::class, 'cancel'])
            ->whereNumber('checkout')
            ->name('sales.checkout.cancel');
    });
