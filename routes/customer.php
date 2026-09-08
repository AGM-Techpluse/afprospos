<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\CustomerAuthController;
use App\Http\Controllers\Customer\DashboardController;
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
        });
    });
