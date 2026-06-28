<?php

use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// Cambodia address cascader (public — geographic reference data)
Route::prefix('addresses')->group(function () {
    Route::get('provinces', [AddressController::class, 'indexProvinces']);
    Route::get('provinces/{province}/districts', [AddressController::class, 'indexDistricts']);
    Route::get('districts/{district}/communes', [AddressController::class, 'indexCommunes']);
    Route::get('communes/{commune}/villages', [AddressController::class, 'indexVillages']);
});

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });
});

Route::middleware(['auth:sanctum'])->prefix('users')->group(function () {
    Route::get('/', [UserController::class, 'index'])->middleware('role:admin,manager');
    Route::post('/', [UserController::class, 'store'])->middleware('role:admin');
    Route::get('{user}', [UserController::class, 'show'])->middleware('role:admin,manager');
    Route::put('{user}', [UserController::class, 'update'])->middleware('role:admin');
    Route::delete('{user}', [UserController::class, 'destroy'])->middleware('role:admin');
});

Route::middleware(['auth:sanctum'])->prefix('categories')->group(function () {
    Route::get('/', [CategoryController::class, 'index'])->middleware('role:admin,manager,cashier,warehouse,purchasing');
    Route::post('/', [CategoryController::class, 'store'])->middleware('role:admin,manager');
    Route::get('{category}', [CategoryController::class, 'show'])->middleware('role:admin,manager,cashier,warehouse,purchasing');
    Route::put('{category}', [CategoryController::class, 'update'])->middleware('role:admin,manager');
    Route::delete('{category}', [CategoryController::class, 'destroy'])->middleware('role:admin,manager');
});

Route::middleware(['auth:sanctum'])->prefix('brands')->group(function () {
    Route::get('/', [BrandController::class, 'index'])->middleware('role:admin,manager,cashier');
    Route::post('/', [BrandController::class, 'store'])->middleware('role:admin,manager');
    Route::get('{brand}', [BrandController::class, 'show'])->middleware('role:admin,manager,cashier');
    Route::put('{brand}', [BrandController::class, 'update'])->middleware('role:admin,manager');
    Route::delete('{brand}', [BrandController::class, 'destroy'])->middleware('role:admin,manager');
});

Route::middleware(['auth:sanctum'])->prefix('suppliers')->group(function () {
    Route::get('/', [SupplierController::class, 'index'])->middleware('role:admin,manager,cashier,warehouse,purchasing');
    Route::post('/', [SupplierController::class, 'store'])->middleware('role:admin,manager');
    Route::get('{supplier}', [SupplierController::class, 'show'])->middleware('role:admin,manager,cashier,warehouse,purchasing');
    Route::put('{supplier}', [SupplierController::class, 'update'])->middleware('role:admin,manager');
    Route::delete('{supplier}', [SupplierController::class, 'destroy'])->middleware('role:admin,manager');
});

Route::middleware(['auth:sanctum'])->prefix('customers')->group(function () {
    Route::get('/', [CustomerController::class, 'index'])->middleware('role:admin,manager,cashier');
    Route::post('/', [CustomerController::class, 'store'])->middleware('role:admin,manager');
    Route::get('{customer}', [CustomerController::class, 'show'])->middleware('role:admin,manager,cashier');
    Route::put('{customer}', [CustomerController::class, 'update'])->middleware('role:admin,manager');
    Route::delete('{customer}', [CustomerController::class, 'destroy'])->middleware('role:admin,manager');
});

// Units
Route::middleware('auth:sanctum')->prefix('units')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\UnitController::class, 'index']);
    Route::post('/', [\App\Http\Controllers\Api\UnitController::class, 'store'])->middleware('role:admin,manager');
    Route::put('/{unit}', [\App\Http\Controllers\Api\UnitController::class, 'update'])->middleware('role:admin,manager');
    Route::delete('/{unit}', [\App\Http\Controllers\Api\UnitController::class, 'destroy'])->middleware('role:admin');
});

// Warehouses
Route::middleware('auth:sanctum')->prefix('warehouses')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\WarehouseController::class, 'index']);
    Route::post('/', [\App\Http\Controllers\Api\WarehouseController::class, 'store'])->middleware('role:admin,manager');
    Route::get('/{warehouse}', [\App\Http\Controllers\Api\WarehouseController::class, 'show']);
    Route::put('/{warehouse}', [\App\Http\Controllers\Api\WarehouseController::class, 'update'])->middleware('role:admin,manager');
    Route::delete('/{warehouse}', [\App\Http\Controllers\Api\WarehouseController::class, 'destroy'])->middleware('role:admin');
});

// Products
Route::middleware('auth:sanctum')->prefix('products')->group(function () {
    Route::get('/lookup', [\App\Http\Controllers\Api\ProductController::class, 'lookup']);
    Route::get('/', [\App\Http\Controllers\Api\ProductController::class, 'index']);
    Route::post('/', [\App\Http\Controllers\Api\ProductController::class, 'store'])->middleware('role:admin,manager');
    Route::get('/{product}', [\App\Http\Controllers\Api\ProductController::class, 'show']);
    Route::put('/{product}', [\App\Http\Controllers\Api\ProductController::class, 'update'])->middleware('role:admin,manager');
    Route::delete('/{product}', [\App\Http\Controllers\Api\ProductController::class, 'destroy'])->middleware('role:admin,manager');

    Route::post('/{product}/variations', [\App\Http\Controllers\Api\ProductVariationController::class, 'store'])->middleware('role:admin,manager');
    Route::put('/{product}/variations/{variation}', [\App\Http\Controllers\Api\ProductVariationController::class, 'update'])->middleware('role:admin,manager');
    Route::delete('/{product}/variations/{variation}', [\App\Http\Controllers\Api\ProductVariationController::class, 'destroy'])->middleware('role:admin,manager');
});

// Batches
Route::middleware('auth:sanctum')->prefix('batches')->group(function () {
    Route::get('/expiring', [\App\Http\Controllers\Api\BatchController::class, 'expiringSoon']);
    Route::get('/expired', [\App\Http\Controllers\Api\BatchController::class, 'expired']);
    Route::get('/', [\App\Http\Controllers\Api\BatchController::class, 'index']);
    Route::post('/', [\App\Http\Controllers\Api\BatchController::class, 'store'])->middleware('role:admin,manager,warehouse,purchasing');
    Route::get('/{batch}', [\App\Http\Controllers\Api\BatchController::class, 'show']);
    Route::put('/{batch}', [\App\Http\Controllers\Api\BatchController::class, 'update'])->middleware('role:admin,manager,warehouse');
    Route::delete('/{batch}', [\App\Http\Controllers\Api\BatchController::class, 'destroy'])->middleware('role:admin,manager');
});

// Sales (POS + history)
Route::middleware('auth:sanctum')->prefix('sales')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\Sales\SaleController::class, 'index'])
        ->middleware('role:admin,manager,cashier,warehouse');
    Route::post('/', [\App\Http\Controllers\Api\Sales\SaleController::class, 'store'])
        ->middleware('role:admin,manager,cashier,warehouse');
    Route::get('{sale}', [\App\Http\Controllers\Api\Sales\SaleController::class, 'show'])
        ->middleware('role:admin,manager,cashier,warehouse');
    Route::post('{sale}/void', [\App\Http\Controllers\Api\Sales\SaleController::class, 'void'])
        ->middleware('role:admin,manager');
});

// Stock movements (audit trail)
Route::middleware('auth:sanctum')->prefix('stock-movements')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\StockMovements\StockMovementController::class, 'index'])
        ->middleware('role:admin,manager,warehouse');
});
