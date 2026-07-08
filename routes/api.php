<?php

use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Rate limiting is applied via middleware (60 requests/min for general API,
| 5 requests/min for authentication endpoints).
|
*/

// Public routes (no authentication required)
Route::group([], function () {
    // Cambodia address cascader (public — geographic reference data)
    Route::prefix('addresses')->group(function () {
        Route::get('provinces', [AddressController::class, 'indexProvinces']);
        Route::get('provinces/{province}/districts', [AddressController::class, 'indexDistricts']);
        Route::get('districts/{district}/communes', [AddressController::class, 'indexCommunes']);
        Route::get('communes/{commune}/villages', [AddressController::class, 'indexVillages']);
    });

    // Authentication routes (stricter rate limiting)
    Route::prefix('auth')->middleware('throttle:auth')->group(function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('register', [AuthController::class, 'register']);

        // Authenticated auth routes
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
        });
    });
});

// API - Protected routes (require authentication)
Route::middleware(['auth:sanctum'])->group(function () {

    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index'])->middleware('permission:view users');
        Route::post('/', [UserController::class, 'store'])->middleware('permission:create users');
        Route::get('{user}', [UserController::class, 'show'])->middleware('permission:view users');
        Route::put('{user}', [UserController::class, 'update'])->middleware('permission:edit users');
        Route::delete('{user}', [UserController::class, 'destroy'])->middleware('permission:delete users');
    });

    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->middleware('permission:view products');
        Route::get('/tree', [CategoryController::class, 'tree'])->middleware('permission:view products');
        Route::post('/', [CategoryController::class, 'store'])->middleware('permission:create products');
        Route::get('{category}', [CategoryController::class, 'show'])->middleware('permission:view products');
        Route::put('{category}', [CategoryController::class, 'update'])->middleware('permission:edit products');
        Route::delete('{category}', [CategoryController::class, 'destroy'])->middleware('permission:delete products');
    });

    Route::prefix('brands')->group(function () {
        Route::get('/', [BrandController::class, 'index'])->middleware('permission:view products');
        Route::post('/', [BrandController::class, 'store'])->middleware('permission:create products');
        Route::get('{brand}', [BrandController::class, 'show'])->middleware('permission:view products');
        Route::put('{brand}', [BrandController::class, 'update'])->middleware('permission:edit products');
        Route::delete('{brand}', [BrandController::class, 'destroy'])->middleware('permission:delete products');
    });

    Route::prefix('suppliers')->group(function () {
        Route::get('/', [SupplierController::class, 'index'])->middleware('permission:view suppliers');
        Route::post('/', [SupplierController::class, 'store'])->middleware('permission:create suppliers');
        Route::get('{supplier}', [SupplierController::class, 'show'])->middleware('permission:view suppliers');
        Route::put('{supplier}', [SupplierController::class, 'update'])->middleware('permission:edit suppliers');
        Route::delete('{supplier}', [SupplierController::class, 'destroy'])->middleware('permission:delete suppliers');
    });

    Route::prefix('customers')->group(function () {
        Route::get('/', [CustomerController::class, 'index'])->middleware('permission:view customers');
        Route::post('/', [CustomerController::class, 'store'])->middleware('permission:create customers');
        Route::get('{customer}', [CustomerController::class, 'show'])->middleware('permission:view customers');
        Route::put('{customer}', [CustomerController::class, 'update'])->middleware('permission:edit customers');
        Route::delete('{customer}', [CustomerController::class, 'destroy'])->middleware('permission:delete customers');
    });

    // Units
    Route::prefix('units')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\UnitController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\UnitController::class, 'store'])->middleware('permission:create products');
        Route::put('/{unit}', [\App\Http\Controllers\Api\UnitController::class, 'update'])->middleware('permission:edit products');
        Route::delete('/{unit}', [\App\Http\Controllers\Api\UnitController::class, 'destroy'])->middleware('permission:delete products');
    });

    // Warehouses
    Route::prefix('warehouses')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\WarehouseController::class, 'index'])->middleware('permission:view warehouses');
        Route::post('/', [\App\Http\Controllers\Api\WarehouseController::class, 'store'])->middleware('permission:create warehouses');
        Route::get('/{warehouse}', [\App\Http\Controllers\Api\WarehouseController::class, 'show'])->middleware('permission:view warehouses');
        Route::put('/{warehouse}', [\App\Http\Controllers\Api\WarehouseController::class, 'update'])->middleware('permission:edit warehouses');
        Route::delete('/{warehouse}', [\App\Http\Controllers\Api\WarehouseController::class, 'destroy'])->middleware('permission:delete warehouses');
    });

    // Products
    Route::prefix('products')->group(function () {
        Route::get('/lookup', [\App\Http\Controllers\Api\ProductController::class, 'lookup'])->middleware('permission:view products');
        Route::get('/lookup/barcode', [\App\Http\Controllers\Api\ProductController::class, 'lookupBarcode'])->middleware('permission:view products');
        Route::get('/', [\App\Http\Controllers\Api\ProductController::class, 'index'])->middleware('permission:view products');
        Route::post('/', [\App\Http\Controllers\Api\ProductController::class, 'store'])->middleware('permission:create products');
        Route::get('/{product}', [\App\Http\Controllers\Api\ProductController::class, 'show'])->middleware('permission:view products');
        Route::get('/{product}/pos-batches', [\App\Http\Controllers\Api\ProductController::class, 'posBatches'])->middleware('permission:view batches');
        Route::put('/{product}', [\App\Http\Controllers\Api\ProductController::class, 'update'])->middleware('permission:edit products');
        Route::delete('/{product}', [\App\Http\Controllers\Api\ProductController::class, 'destroy'])->middleware('permission:delete products');

        Route::post('/{product}/variations', [\App\Http\Controllers\Api\ProductVariationController::class, 'store'])->middleware('permission:create products');
        Route::put('/{product}/variations/{variation}', [\App\Http\Controllers\Api\ProductVariationController::class, 'update'])->middleware('permission:edit products');
        Route::delete('/{product}/variations/{variation}', [\App\Http\Controllers\Api\ProductVariationController::class, 'destroy'])->middleware('permission:delete products');
    });

    // Batches
    Route::prefix('batches')->group(function () {
        Route::get('/expiring', [\App\Http\Controllers\Api\BatchController::class, 'expiringSoon'])->middleware('permission:view batches');
        Route::get('/expired', [\App\Http\Controllers\Api\BatchController::class, 'expired'])->middleware('permission:view batches');
        Route::get('/', [\App\Http\Controllers\Api\BatchController::class, 'index'])->middleware('permission:view batches');
        Route::post('/', [\App\Http\Controllers\Api\BatchController::class, 'store'])->middleware('permission:create batches');
        Route::get('/{batch}', [\App\Http\Controllers\Api\BatchController::class, 'show'])->middleware('permission:view batches');
        Route::put('/{batch}', [\App\Http\Controllers\Api\BatchController::class, 'update'])->middleware('permission:edit batches');
        Route::delete('/{batch}', [\App\Http\Controllers\Api\BatchController::class, 'destroy'])->middleware('permission:delete batches');
    });

    // Sales (POS + history)
    Route::prefix('sales')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Sales\SaleController::class, 'index'])->middleware('permission:view reports');
        Route::post('/', [\App\Http\Controllers\Api\Sales\SaleController::class, 'store'])->middleware('permission:create invoices');
        Route::get('{sale}', [\App\Http\Controllers\Api\Sales\SaleController::class, 'show'])->middleware('permission:view reports');
        Route::post('{sale}/void', [\App\Http\Controllers\Api\Sales\SaleController::class, 'void'])->middleware('permission:cancel sales');
    });

    // Draft Orders (POS hold / recall)
    Route::prefix('draft-orders')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\DraftOrderController::class, 'index'])->middleware('permission:access pos');
        Route::post('/', [\App\Http\Controllers\Api\DraftOrderController::class, 'store'])->middleware('permission:access pos');
        Route::get('/{draftOrder}', [\App\Http\Controllers\Api\DraftOrderController::class, 'show'])->middleware('permission:access pos');
        Route::put('/{draftOrder}', [\App\Http\Controllers\Api\DraftOrderController::class, 'update'])->middleware('permission:access pos');
        Route::delete('/{draftOrder}', [\App\Http\Controllers\Api\DraftOrderController::class, 'destroy'])->middleware('permission:access pos');
    });

    // Stock movements (audit trail)
    Route::prefix('stock-movements')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\StockMovements\StockMovementController::class, 'index'])->middleware('permission:view inventory');
    });

    // Inventory operations
    Route::get('/purchase-receipts', [InventoryController::class, 'indexPurchaseReceipts']);
    Route::post('/purchase-receipts', [InventoryController::class, 'storePurchaseReceipt']);
    Route::get('/refunds', [InventoryController::class, 'indexRefunds']);
    Route::post('/refunds', [InventoryController::class, 'storeRefund']);
    Route::get('/stock-transfers', [InventoryController::class, 'indexStockTransfers']);
    Route::post('/stock-transfers', [InventoryController::class, 'storeStockTransfer']);
    Route::get('/stock-adjustments', [InventoryController::class, 'indexStockAdjustments']);
    Route::post('/stock-adjustments', [InventoryController::class, 'storeStockAdjustment']);

    // Purchase Orders
    Route::get('/purchase-orders', [InventoryController::class, 'indexPurchaseOrders'])->middleware('permission:view inventory');
    Route::post('/purchase-orders', [InventoryController::class, 'storePurchaseOrder'])->middleware('permission:create invoices');
    Route::get('/purchase-orders/{purchaseOrder}', [InventoryController::class, 'showPurchaseOrder'])->middleware('permission:view inventory');
    Route::put('/purchase-orders/{purchaseOrder}', [InventoryController::class, 'updatePurchaseOrder'])->middleware('permission:edit invoices');
    Route::delete('/purchase-orders/{purchaseOrder}', [InventoryController::class, 'destroyPurchaseOrder'])->middleware('permission:cancel sales');
    Route::post('/purchase-orders/{purchaseOrder}/submit-for-approval', [InventoryController::class, 'submitForApproval'])->middleware('permission:create invoices');
    Route::post('/purchase-orders/{purchaseOrder}/convert-to-receipt', [InventoryController::class, 'convertToReceipt'])->middleware('permission:create invoices');

    // Supplier Payments
    Route::get('/supplier-payments', [InventoryController::class, 'indexSupplierPayments'])->middleware('permission:view inventory');
    Route::post('/supplier-payments', [InventoryController::class, 'storeSupplierPayment'])->middleware('permission:create invoices');
    Route::get('/supplier-payments/{supplierPayment}', [InventoryController::class, 'showSupplierPayment'])->middleware('permission:view inventory');
    Route::put('/supplier-payments/{supplierPayment}', [InventoryController::class, 'updateSupplierPayment'])->middleware('permission:edit invoices');
    Route::delete('/supplier-payments/{supplierPayment}', [InventoryController::class, 'destroySupplierPayment'])->middleware('permission:cancel sales');

}); // End of authenticated routes group
