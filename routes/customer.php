<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\CustomerAuthController;
use App\Http\Controllers\Customer\DashboardController;
use App\Http\Controllers\Customer\OrdersController;
use App\Http\Controllers\Customer\ProfileController;
use App\Http\Controllers\Customer\RepairsController;
use App\Http\Controllers\Customer\WarrantyClaimController;
use Illuminate\Support\Facades\Route;

Route::prefix('customer')
    ->name('customer.')
    ->group(function (): void {
        Route::middleware('guest:customer')->group(function (): void {
            Route::get('/login', [CustomerAuthController::class, 'createLogin'])->name('login');
            Route::post('/login', [CustomerAuthController::class, 'storeLogin'])->name('login.store');
            Route::get('/register', [CustomerAuthController::class, 'createRegister'])->name('register');
            Route::post('/register', [CustomerAuthController::class, 'storeRegister'])->name('register.store');
        });

        Route::post('/logout', [CustomerAuthController::class, 'destroy'])
            ->middleware('auth:customer')
            ->name('logout');

        Route::middleware('auth:customer')->group(function (): void {
            Route::get('/dashboard', DashboardController::class)->name('dashboard');
            Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
            Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

            Route::get('/orders', [OrdersController::class, 'index'])->name('orders.index');
            Route::get('/orders/checkouts/{checkout}', [OrdersController::class, 'showCheckout'])
                ->whereNumber('checkout')
                ->name('orders.checkouts.show');
            Route::get('/orders/sales/{sale}', [OrdersController::class, 'showSale'])
                ->whereNumber('sale')
                ->name('orders.sales.show');

            Route::get('/repairs', [RepairsController::class, 'index'])->name('repairs.index');
            Route::get('/repairs/{repair}', [RepairsController::class, 'show'])
                ->whereNumber('repair')
                ->name('repairs.show');

            Route::get('/warranty/claims', [WarrantyClaimController::class, 'index'])->name('warranty.claims.index');
            Route::get('/warranty/claims/create', [WarrantyClaimController::class, 'create'])->name('warranty.claims.create');
            Route::post('/warranty/claims', [WarrantyClaimController::class, 'store'])->name('warranty.claims.store');
            Route::get('/warranty/claims/{claim}', [WarrantyClaimController::class, 'show'])
                ->whereNumber('claim')
                ->name('warranty.claims.show');
        });
    });
