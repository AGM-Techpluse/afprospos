<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\CollectionController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DeviceCatalogController;
use App\Http\Controllers\Admin\InventoryImportController;
use App\Http\Controllers\Admin\InventorySerializedUnitsController;
use App\Http\Controllers\Admin\InventoryStockController;
use App\Http\Controllers\Admin\InventoryTransferController;
use App\Http\Controllers\Admin\NotificationSettingsController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\RepairsController;
use App\Http\Controllers\Admin\ReturnRequestController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SalesController;
use App\Http\Controllers\Admin\ShopController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\TradeInAssessmentController;
use App\Http\Controllers\Admin\WarrantyClaimController;
use App\Http\Controllers\Admin\WarrantyPolicyController;
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

        Route::middleware('permission:inventory.view')
            ->get('/inventory/serialized-units', [InventorySerializedUnitsController::class, 'index'])
            ->name('inventory.serialized-units.index');

        Route::middleware('permission:inventory.edit')
            ->post('/inventory/stock/bulk-adjust', [InventoryStockController::class, 'bulkAdjust'])
            ->name('inventory.stock.bulk-adjust');

        Route::middleware('permission:inventory.edit')
            ->post('/inventory/stock/bulk-transfer', [InventoryStockController::class, 'bulkTransfer'])
            ->name('inventory.stock.bulk-transfer');

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
            ->get('/sales/customers/search', [SalesController::class, 'searchCustomers'])
            ->name('sales.customers.search');

        Route::middleware('permission:sales.create')
            ->post('/sales/checkout', [SalesController::class, 'store'])
            ->name('sales.checkout.store');

        Route::middleware('permission:sales.create')
            ->post('/sales/checkout/{checkout}/items', [SalesController::class, 'addItem'])
            ->whereNumber('checkout')
            ->name('sales.checkout.items.store');

        Route::middleware('permission:sales.create')
            ->patch('/sales/checkout/{checkout}/items/{item}', [SalesController::class, 'updateItemQuantity'])
            ->whereNumber(['checkout', 'item'])
            ->name('sales.checkout.items.update');

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

        Route::middleware('permission:payments.view')
            ->get('/payments', [PaymentController::class, 'index'])
            ->name('payments.index');

        Route::middleware('permission:payments.view')
            ->get('/payments/export', [PaymentController::class, 'export'])
            ->name('payments.export');

        Route::middleware('permission:payments.view')
            ->get('/payments/{payment}', [PaymentController::class, 'show'])
            ->whereNumber('payment')
            ->name('payments.show');

        Route::middleware('permission:payments.confirm')
            ->post('/payments/{payment}/confirm', [PaymentController::class, 'confirm'])
            ->whereNumber('payment')
            ->name('payments.confirm');

        Route::middleware('permission:payments.reject')
            ->post('/payments/{payment}/reject', [PaymentController::class, 'reject'])
            ->whereNumber('payment')
            ->name('payments.reject');

        Route::middleware('permission:payments.dispute')
            ->post('/payments/{payment}/dispute', [PaymentController::class, 'openDispute'])
            ->whereNumber('payment')
            ->name('payments.dispute');

        Route::middleware('permission:payments.dispute')
            ->post('/payments/{payment}/dispute/resolve', [PaymentController::class, 'resolveDispute'])
            ->whereNumber('payment')
            ->name('payments.dispute.resolve');

        Route::middleware('permission:payments.refund')
            ->post('/payments/{payment}/refund', [PaymentController::class, 'refund'])
            ->whereNumber('payment')
            ->name('payments.refund');

        /*
        |----------------------------------------------------------------
        | Repair intake device catalog (Settings)
        |----------------------------------------------------------------
        */
        Route::middleware('permission:repairs.manage')
            ->get('/settings/device-catalog', [DeviceCatalogController::class, 'index'])
            ->name('settings.device-catalog.index');

        Route::middleware('permission:repairs.manage')
            ->get('/settings/device-catalog/parts/search', [DeviceCatalogController::class, 'searchParts'])
            ->name('settings.device-catalog.parts.search');

        Route::middleware('permission:repairs.manage')
            ->post('/settings/device-catalog/types', [DeviceCatalogController::class, 'storeType'])
            ->name('settings.device-catalog.types.store');

        Route::middleware('permission:repairs.manage')
            ->put('/settings/device-catalog/types/{type}', [DeviceCatalogController::class, 'updateType'])
            ->whereNumber('type')
            ->name('settings.device-catalog.types.update');

        Route::middleware('permission:repairs.manage')
            ->delete('/settings/device-catalog/types/{type}', [DeviceCatalogController::class, 'destroyType'])
            ->whereNumber('type')
            ->name('settings.device-catalog.types.destroy');

        Route::middleware('permission:repairs.manage')
            ->post('/settings/device-catalog/brands', [DeviceCatalogController::class, 'storeBrand'])
            ->name('settings.device-catalog.brands.store');

        Route::middleware('permission:repairs.manage')
            ->put('/settings/device-catalog/brands/{brand}', [DeviceCatalogController::class, 'updateBrand'])
            ->whereNumber('brand')
            ->name('settings.device-catalog.brands.update');

        Route::middleware('permission:repairs.manage')
            ->delete('/settings/device-catalog/brands/{brand}', [DeviceCatalogController::class, 'destroyBrand'])
            ->whereNumber('brand')
            ->name('settings.device-catalog.brands.destroy');

        Route::middleware('permission:repairs.manage')
            ->post('/settings/device-catalog/problem-tags', [DeviceCatalogController::class, 'storeProblemTag'])
            ->name('settings.device-catalog.problem-tags.store');

        Route::middleware('permission:repairs.manage')
            ->put('/settings/device-catalog/problem-tags/{tag}', [DeviceCatalogController::class, 'updateProblemTag'])
            ->whereNumber('tag')
            ->name('settings.device-catalog.problem-tags.update');

        Route::middleware('permission:repairs.manage')
            ->delete('/settings/device-catalog/problem-tags/{tag}', [DeviceCatalogController::class, 'destroyProblemTag'])
            ->whereNumber('tag')
            ->name('settings.device-catalog.problem-tags.destroy');

        Route::middleware('permission:repairs.manage')
            ->post('/settings/device-catalog/problem-tags/{tag}/suggested-parts', [DeviceCatalogController::class, 'attachSuggestedPart'])
            ->whereNumber('tag')
            ->name('settings.device-catalog.suggested-parts.store');

        Route::middleware('permission:repairs.manage')
            ->delete('/settings/device-catalog/suggested-parts/{suggestedPart}', [DeviceCatalogController::class, 'detachSuggestedPart'])
            ->whereNumber('suggestedPart')
            ->name('settings.device-catalog.suggested-parts.destroy');

        Route::middleware('permission:settings.view')
            ->get('/settings/notifications', [NotificationSettingsController::class, 'index'])
            ->name('settings.notifications.index');

        Route::middleware('permission:repairs.view')
            ->get('/repairs', [RepairsController::class, 'index'])
            ->name('repairs.index');

        Route::middleware('permission:repairs.view')
            ->get('/repairs/export', [RepairsController::class, 'export'])
            ->name('repairs.export');

        Route::middleware('permission:repairs.create')
            ->get('/repairs/create', [RepairsController::class, 'create'])
            ->name('repairs.create');

        Route::middleware('permission:repairs.create')
            ->post('/repairs', [RepairsController::class, 'store'])
            ->name('repairs.store');

        Route::middleware('permission:repairs.create')
            ->get('/repairs/customers/search', [RepairsController::class, 'searchCustomers'])
            ->name('repairs.customers.search');

        Route::middleware('permission:repairs.view')
            ->get('/repairs/{repair}', [RepairsController::class, 'show'])
            ->whereNumber('repair')
            ->name('repairs.show');

        Route::middleware('permission:repairs.view')
            ->get('/repairs/{repair}/diagnosis', [RepairsController::class, 'diagnosis'])
            ->whereNumber('repair')
            ->name('repairs.diagnosis');

        Route::middleware('permission:repairs.edit')
            ->post('/repairs/{repair}/diagnosis', [RepairsController::class, 'recordDiagnosis'])
            ->whereNumber('repair')
            ->name('repairs.diagnosis.store');

        Route::middleware('permission:repairs.approve')
            ->post('/repairs/{repair}/authorize', [RepairsController::class, 'authorize'])
            ->whereNumber('repair')
            ->name('repairs.authorize');

        Route::middleware('permission:repairs.approve')
            ->post('/repairs/{repair}/confirm-down-payment', [RepairsController::class, 'confirmDownPayment'])
            ->whereNumber('repair')
            ->name('repairs.confirm-down-payment');

        Route::middleware('permission:repairs.view')
            ->get('/repairs/{repair}/parts', [RepairsController::class, 'parts'])
            ->whereNumber('repair')
            ->name('repairs.parts');

        Route::middleware('permission:repairs.view')
            ->get('/repairs/{repair}/parts/search', [RepairsController::class, 'searchParts'])
            ->whereNumber('repair')
            ->name('repairs.parts.search');

        Route::middleware('permission:repairs.view')
            ->get('/repairs/{repair}/collection', [RepairsController::class, 'collection'])
            ->whereNumber('repair')
            ->name('repairs.collection');

        Route::middleware('permission:repairs.edit')
            ->post('/repairs/{repair}/parts', [RepairsController::class, 'reserveParts'])
            ->whereNumber('repair')
            ->name('repairs.parts.reserve');

        Route::middleware('permission:repairs.edit')
            ->post('/repairs/{repair}/parts/{reservation}/install', [RepairsController::class, 'installPart'])
            ->whereNumber('repair')
            ->whereNumber('reservation')
            ->name('repairs.parts.install');

        Route::middleware('permission:repairs.assign')
            ->post('/repairs/{repair}/assign-technician', [RepairsController::class, 'assignTechnician'])
            ->whereNumber('repair')
            ->name('repairs.assign-technician');

        Route::middleware('permission:repairs.edit')
            ->post('/repairs/{repair}/device-lock', [RepairsController::class, 'updateDeviceLock'])
            ->whereNumber('repair')
            ->name('repairs.device-lock.update');

        Route::middleware('permission:repairs.edit')
            ->post('/repairs/{repair}/photos', [RepairsController::class, 'uploadPhoto'])
            ->whereNumber('repair')
            ->name('repairs.photos.store');

        Route::middleware('permission:repairs.edit')
            ->delete('/repairs/{repair}/photos/{photo}', [RepairsController::class, 'deletePhoto'])
            ->whereNumber(['repair', 'photo'])
            ->name('repairs.photos.destroy');

        Route::middleware('permission:repairs.edit')
            ->post('/repairs/{repair}/start', [RepairsController::class, 'start'])
            ->whereNumber('repair')
            ->name('repairs.start');

        Route::middleware('permission:repairs.edit')
            ->post('/repairs/{repair}/complete', [RepairsController::class, 'complete'])
            ->whereNumber('repair')
            ->name('repairs.complete');

        Route::middleware('permission:repairs.edit')
            ->post('/repairs/{repair}/fail', [RepairsController::class, 'fail'])
            ->whereNumber('repair')
            ->name('repairs.fail');

        Route::middleware('permission:repairs.edit')
            ->post('/repairs/{repair}/resume', [RepairsController::class, 'resume'])
            ->whereNumber('repair')
            ->name('repairs.resume');

        Route::middleware('permission:repairs.edit')
            ->post('/repairs/{repair}/unrepairable', [RepairsController::class, 'markUnrepairable'])
            ->whereNumber('repair')
            ->name('repairs.unrepairable');

        Route::middleware('permission:collection.view')
            ->get('/collection', [CollectionController::class, 'index'])
            ->name('collection.index');

        Route::middleware('permission:collection.view')
            ->get('/collection/{collectionCase}', [CollectionController::class, 'show'])
            ->whereNumber('collectionCase')
            ->name('collection.show');

        Route::middleware('permission:collection.process')
            ->post('/collection/{collectionCase}/notify', [CollectionController::class, 'notify'])
            ->whereNumber('collectionCase')
            ->name('collection.notify');

        Route::middleware('permission:collection.process')
            ->post('/collection/{collectionCase}/extend', [CollectionController::class, 'extend'])
            ->whereNumber('collectionCase')
            ->name('collection.extend');

        Route::middleware('permission:collection.override')
            ->post('/collection/{collectionCase}/override', [CollectionController::class, 'override'])
            ->whereNumber('collectionCase')
            ->name('collection.override');

        Route::middleware('permission:collection.process')
            ->post('/collection/{collectionCase}/release', [CollectionController::class, 'release'])
            ->whereNumber('collectionCase')
            ->name('collection.release');

        Route::middleware('permission:warranty.manage')
            ->get('/warranty/policies', [WarrantyPolicyController::class, 'index'])
            ->name('warranty.policies.index');

        Route::middleware('permission:warranty.manage')
            ->get('/warranty/policies/create', [WarrantyPolicyController::class, 'create'])
            ->name('warranty.policies.create');

        Route::middleware('permission:warranty.manage')
            ->post('/warranty/policies', [WarrantyPolicyController::class, 'store'])
            ->name('warranty.policies.store');

        Route::middleware('permission:warranty.manage')
            ->get('/warranty/policies/{policy}/edit', [WarrantyPolicyController::class, 'edit'])
            ->whereNumber('policy')
            ->name('warranty.policies.edit');

        Route::middleware('permission:warranty.manage')
            ->put('/warranty/policies/{policy}', [WarrantyPolicyController::class, 'update'])
            ->whereNumber('policy')
            ->name('warranty.policies.update');

        Route::middleware('permission:warranty.manage')
            ->delete('/warranty/policies/{policy}', [WarrantyPolicyController::class, 'destroy'])
            ->whereNumber('policy')
            ->name('warranty.policies.destroy');

        Route::middleware('permission:warranty.view')
            ->get('/warranty/claims', [WarrantyClaimController::class, 'index'])
            ->name('warranty.claims.index');

        Route::middleware('permission:warranty.create')
            ->get('/warranty/claims/create', [WarrantyClaimController::class, 'create'])
            ->name('warranty.claims.create');

        Route::middleware('permission:warranty.create')
            ->get('/warranty/claims/search-customers', [WarrantyClaimController::class, 'searchCustomers'])
            ->name('warranty.claims.search-customers');

        Route::middleware('permission:warranty.create')
            ->post('/warranty/claims', [WarrantyClaimController::class, 'store'])
            ->name('warranty.claims.store');

        Route::middleware('permission:warranty.view')
            ->get('/warranty/claims/{claim}', [WarrantyClaimController::class, 'show'])
            ->whereNumber('claim')
            ->name('warranty.claims.show');

        Route::middleware('permission:warranty.assess')
            ->post('/warranty/claims/{claim}/assess', [WarrantyClaimController::class, 'assess'])
            ->whereNumber('claim')
            ->name('warranty.claims.assess');

        Route::middleware('permission:warranty.assess')
            ->post('/warranty/claims/{claim}/select-remedy', [WarrantyClaimController::class, 'selectRemedy'])
            ->whereNumber('claim')
            ->name('warranty.claims.select-remedy');

        Route::middleware('permission:warranty.resolve')
            ->post('/warranty/claims/{claim}/resolve', [WarrantyClaimController::class, 'resolve'])
            ->whereNumber('claim')
            ->name('warranty.claims.resolve');

        Route::middleware('permission:warranty.approve-refund')
            ->post('/warranty/claims/{claim}/approve-refund', [WarrantyClaimController::class, 'approveRefund'])
            ->whereNumber('claim')
            ->name('warranty.claims.approve-refund');

        Route::middleware('permission:warranty.view')
            ->get('/warranty/returns', [ReturnRequestController::class, 'index'])
            ->name('warranty.returns.index');

        Route::middleware('permission:warranty.view')
            ->get('/warranty/returns/{returnRequest}', [ReturnRequestController::class, 'show'])
            ->whereNumber('returnRequest')
            ->name('warranty.returns.show');

        Route::middleware('permission:warranty.assess')
            ->post('/warranty/returns/{returnRequest}/assess', [ReturnRequestController::class, 'assess'])
            ->whereNumber('returnRequest')
            ->name('warranty.returns.assess');

        Route::middleware('permission:warranty.resolve')
            ->post('/warranty/returns/{returnRequest}/approve', [ReturnRequestController::class, 'approve'])
            ->whereNumber('returnRequest')
            ->name('warranty.returns.approve');

        Route::middleware('permission:warranty.resolve')
            ->post('/warranty/returns/{returnRequest}/deny', [ReturnRequestController::class, 'deny'])
            ->whereNumber('returnRequest')
            ->name('warranty.returns.deny');

        Route::middleware('permission:warranty.resolve')
            ->post('/warranty/returns/{returnRequest}/override-approve', [ReturnRequestController::class, 'overrideApprove'])
            ->whereNumber('returnRequest')
            ->name('warranty.returns.override-approve');

        Route::middleware('permission:warranty.approve-refund')
            ->post('/warranty/returns/{returnRequest}/process-refund', [ReturnRequestController::class, 'processRefund'])
            ->whereNumber('returnRequest')
            ->name('warranty.returns.process-refund');

        Route::middleware('permission:warranty.view')
            ->get('/warranty/trade-ins', [TradeInAssessmentController::class, 'index'])
            ->name('warranty.trade-ins.index');

        Route::middleware('permission:warranty.create')
            ->get('/warranty/trade-ins/create', [TradeInAssessmentController::class, 'create'])
            ->name('warranty.trade-ins.create');

        Route::middleware('permission:warranty.create')
            ->get('/warranty/trade-ins/search-customers', [TradeInAssessmentController::class, 'searchCustomers'])
            ->name('warranty.trade-ins.search-customers');

        Route::middleware('permission:warranty.create')
            ->post('/warranty/trade-ins', [TradeInAssessmentController::class, 'store'])
            ->name('warranty.trade-ins.store');

        Route::middleware('permission:warranty.view')
            ->get('/warranty/trade-ins/{tradeIn}', [TradeInAssessmentController::class, 'show'])
            ->whereNumber('tradeIn')
            ->name('warranty.trade-ins.show');

        Route::middleware('permission:warranty.assess')
            ->post('/warranty/trade-ins/{tradeIn}/assess', [TradeInAssessmentController::class, 'assess'])
            ->whereNumber('tradeIn')
            ->name('warranty.trade-ins.assess');

        Route::middleware('permission:warranty.resolve')
            ->post('/warranty/trade-ins/{tradeIn}/approve', [TradeInAssessmentController::class, 'approve'])
            ->whereNumber('tradeIn')
            ->name('warranty.trade-ins.approve');

        Route::middleware('permission:warranty.resolve')
            ->post('/warranty/trade-ins/{tradeIn}/reject', [TradeInAssessmentController::class, 'reject'])
            ->whereNumber('tradeIn')
            ->name('warranty.trade-ins.reject');

        Route::middleware('permission:warranty.resolve')
            ->post('/warranty/trade-ins/{tradeIn}/apply-credit', [TradeInAssessmentController::class, 'applyCredit'])
            ->whereNumber('tradeIn')
            ->name('warranty.trade-ins.apply-credit');
    });
