<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\SalesController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\ZReportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Public health check
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String()
    ]);
});

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    
    // Companies
    Route::prefix('companies')->group(function () {
        Route::get('/', [CompanyController::class, 'index']);
        Route::post('/', [CompanyController::class, 'store']);
        Route::post('/get-or-create', [CompanyController::class, 'getOrCreate']);
        Route::get('/{id}', [CompanyController::class, 'show']);
        Route::put('/{id}', [CompanyController::class, 'update']);
        Route::delete('/{id}', [CompanyController::class, 'destroy']);
    });

    // Sales
    Route::prefix('sales')->group(function () {
        Route::get('/', [SalesController::class, 'index']);
        Route::post('/', [SalesController::class, 'store']);
        Route::post('/sync-batch', [SalesController::class, 'syncBatch']);
        Route::get('/{id}', [SalesController::class, 'show']);
        Route::put('/{id}', [SalesController::class, 'update']);
        Route::delete('/{id}', [SalesController::class, 'destroy']);
        
        // Sales statistics
        Route::get('/stats/summary', [SalesController::class, 'summary']);
        Route::get('/stats/by-date', [SalesController::class, 'byDate']);
    });

    // Products
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::post('/', [ProductController::class, 'store']);
        Route::post('/sync-batch', [ProductController::class, 'syncBatch']);
        Route::get('/{id}', [ProductController::class, 'show']);
        Route::put('/{id}', [ProductController::class, 'update']);
        Route::delete('/{id}', [ProductController::class, 'destroy']);
    });

    // Stock/Inventory
    Route::prefix('stocks')->group(function () {
        Route::get('/', [StockController::class, 'index']);
        Route::get('/low-stock', [StockController::class, 'lowStock']);
        Route::put('/{id}', [StockController::class, 'update']);
        Route::post('/sync-batch', [StockController::class, 'syncBatch']);
        Route::post('/products/{productId}/adjust', [StockController::class, 'adjust']);
        Route::get('/products/{productId}/movements', [StockController::class, 'movements']);
    });

    // Purchases
    Route::prefix('purchases')->group(function () {
        Route::get('/', [PurchaseController::class, 'index']);
        Route::post('/', [PurchaseController::class, 'store']);
        Route::post('/sync-batch', [PurchaseController::class, 'syncBatch']);
        Route::get('/{id}', [PurchaseController::class, 'show']);
        Route::put('/{id}', [PurchaseController::class, 'update']);
        Route::delete('/{id}', [PurchaseController::class, 'destroy']);
    });

    // Z-Reports
    Route::prefix('z-reports')->group(function () {
        Route::get('/', [ZReportController::class, 'index']);
        Route::post('/', [ZReportController::class, 'store']);
        Route::post('/generate', [ZReportController::class, 'generate']);
        Route::post('/sync-batch', [ZReportController::class, 'syncBatch']);
        Route::get('/summary', [ZReportController::class, 'summary']);
        Route::get('/{id}', [ZReportController::class, 'show']);
        Route::put('/{id}', [ZReportController::class, 'update']);
        Route::delete('/{id}', [ZReportController::class, 'destroy']);
    });
});