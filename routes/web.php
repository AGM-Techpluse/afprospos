<?php

use App\Http\Controllers\ShopController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [WelcomeController::class, 'show'])->name('home');

Route::middleware('storefront.enabled')->group(function (): void {
    Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
    Route::get('/shop/{sku}', [ShopController::class, 'show'])->whereNumber('sku')->name('shop.show');
    Route::get('/shop/{sku}/image', [ShopController::class, 'image'])->whereNumber('sku')->name('shop.image');
});
