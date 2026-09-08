<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\DashboardController;
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
            ->post('/staff', [StaffController::class, 'store'])
            ->name('staff.store');

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

        Route::middleware('permission:shops.create')
            ->post('/shops', [ShopController::class, 'store'])
            ->name('shops.store');

        // Not permission-gated: any authenticated staff member may switch
        // among shops they already hold an active grant for (or, for the
        // owner, any active shop). The actual scope check happens inside
        // SwitchActiveShopHandler via RBAC's ShopScopePolicy — this route
        // deliberately does not duplicate that check.
        Route::post('/shops/switch', [ShopController::class, 'switch'])->name('shops.switch');
    });
